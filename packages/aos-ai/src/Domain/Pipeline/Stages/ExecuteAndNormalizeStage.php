<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Pipeline\Stages;

use DressnMore\Aos\Ai\Domain\Cost\CostManager;
use DressnMore\Aos\Ai\Domain\Fallback\FallbackManager;
use DressnMore\Aos\Ai\Domain\Health\HealthStatus;
use DressnMore\Aos\Ai\Domain\Health\ProviderHealthMonitor;
use DressnMore\Aos\Ai\Domain\Metrics\ProviderMetrics;
use DressnMore\Aos\Ai\Domain\Pipeline\AiPipelineBag;
use DressnMore\Aos\Ai\Domain\Pipeline\AiPipelineStage;
use DressnMore\Aos\Ai\Domain\Pipeline\AiPipelineStageInterface;
use DressnMore\Aos\Ai\Domain\Provider\ProviderRegistryInterface;
use DressnMore\Aos\Ai\Domain\Response\AiResponse;
use DressnMore\Aos\Ai\Domain\Response\FinishReason;
use DressnMore\Aos\Ai\Domain\Retry\RetryManager;
use DressnMore\Aos\Ai\Domain\Streaming\StreamChunk;
use RuntimeException;

final class ExecuteAndNormalizeStage implements AiPipelineStageInterface
{
    public function __construct(
        private readonly ProviderRegistryInterface $providers,
        private readonly RetryManager $retry,
        private readonly FallbackManager $fallback,
        private readonly ProviderHealthMonitor $health,
        private readonly ProviderMetrics $metrics,
        private readonly CostManager $cost,
    ) {}

    public function name(): AiPipelineStage
    {
        return AiPipelineStage::ResponseNormalization;
    }

    public function process(AiPipelineBag $bag): void
    {
        $selection = $bag->selection();
        if ($selection === null) {
            throw new RuntimeException('No provider selected for execution.');
        }

        $ranked = $bag->rankedSelections();
        $current = $selection;
        $lastError = null;

        while ($current !== null) {
            $plugin = $this->providers->plugin($current->provider()->id());
            if ($plugin === null) {
                $lastError = new RuntimeException('Missing plugin for '.$current->provider()->id()->toString());
                $current = $this->fallback->next($ranked, $current->provider()->id());
                $bag->markFallbackUsed();
                continue;
            }

            $request = $bag->request()->withPreferredProvider($current->provider()->id())
                ->withPreferredModel($current->model()->id());

            try {
                if ($request->streaming()) {
                    $chunks = $this->retry->run(static function () use ($plugin, $request): array {
                        return iterator_to_array($plugin->stream($request), false);
                    });
                    $bag->setStreamChunks($chunks);
                    $text = '';
                    foreach ($chunks as $chunk) {
                        /** @var StreamChunk $chunk */
                        $text .= $chunk->delta();
                    }
                    $raw = $plugin->complete($request);
                    $usage = $raw->usage();
                    $cost = $this->cost->calculate($current->model(), $usage);
                    $normalized = new AiResponse(
                        trim($text) !== '' ? trim($text) : $raw->completion(),
                        $current->provider()->id(),
                        $current->model()->id(),
                        $usage,
                        $raw->latencyMs(),
                        $cost,
                        $bag->fallbackUsed() ? FinishReason::Fallback : FinishReason::Stop,
                        array_merge($raw->metadata(), ['streaming' => true]),
                        $bag->fallbackUsed(),
                    );
                } else {
                    $normalized = $this->retry->run(static function () use ($plugin, $request, $current, $bag) {
                        $raw = $plugin->complete($request);

                        return new AiResponse(
                            $raw->completion(),
                            $current->provider()->id(),
                            $current->model()->id(),
                            $raw->usage(),
                            $raw->latencyMs(),
                            $raw->costUsd(),
                            $bag->fallbackUsed() ? FinishReason::Fallback : $raw->finishReason(),
                            $raw->metadata(),
                            $bag->fallbackUsed(),
                        );
                    });
                    $cost = $this->cost->calculate($current->model(), $normalized->usage());
                    $normalized = new AiResponse(
                        $normalized->completion(),
                        $normalized->providerId(),
                        $normalized->modelId(),
                        $normalized->usage(),
                        $normalized->latencyMs(),
                        $cost,
                        $normalized->finishReason(),
                        $normalized->metadata(),
                        $normalized->fromFallback(),
                    );
                }

                $bag->setResponse($normalized);
                $bag->setSelection($current);
                $this->health->markHealthy($current->provider()->id());
                $this->metrics->recordSuccess($current->provider()->id(), $normalized->latencyMs(), $normalized->costUsd());
                $bag->mark(AiPipelineStage::Execution->value);

                return;
            } catch (\Throwable $e) {
                $lastError = $e;
                $this->health->mark($current->provider()->id(), HealthStatus::Unhealthy);
                $this->metrics->recordFailure($current->provider()->id());
                $failedId = $current->provider()->id();
                $current = $this->fallback->next($ranked, $failedId);
                if ($current !== null) {
                    $bag->markFallbackUsed();
                    $bag->setSelection($current);
                }
            }
        }

        throw new RuntimeException('All providers failed: '.($lastError?->getMessage() ?? 'unknown'));
    }
}

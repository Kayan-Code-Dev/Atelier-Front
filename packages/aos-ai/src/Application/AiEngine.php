<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Application;

use DressnMore\Aos\Ai\Contracts\AiEngineInterface;
use DressnMore\Aos\Ai\Domain\Capability\CapabilityRegistry;
use DressnMore\Aos\Ai\Domain\Cost\CostManager;
use DressnMore\Aos\Ai\Domain\Events\BudgetExceeded;
use DressnMore\Aos\Ai\Domain\Events\CompletionReceived;
use DressnMore\Aos\Ai\Domain\Events\CompletionRequested;
use DressnMore\Aos\Ai\Domain\Events\FallbackActivated;
use DressnMore\Aos\Ai\Domain\Events\ProviderSelected;
use DressnMore\Aos\Ai\Domain\Events\StreamingCompleted;
use DressnMore\Aos\Ai\Domain\Events\StreamingStarted;
use DressnMore\Aos\Ai\Domain\Factory\ProviderFactory;
use DressnMore\Aos\Ai\Domain\Fallback\FallbackManager;
use DressnMore\Aos\Ai\Domain\Health\ProviderHealthMonitor;
use DressnMore\Aos\Ai\Domain\Metrics\ProviderMetrics;
use DressnMore\Aos\Ai\Domain\Model\ModelRegistry;
use DressnMore\Aos\Ai\Domain\Model\ModelResolver;
use DressnMore\Aos\Ai\Domain\Policies\ProviderPolicyEngine;
use DressnMore\Aos\Ai\Domain\Provider\ProviderManager;
use DressnMore\Aos\Ai\Domain\Provider\ProviderRegistry;
use DressnMore\Aos\Ai\Domain\Provider\ProviderResolver;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;
use DressnMore\Aos\Ai\Domain\Request\CompletionEngine;
use DressnMore\Aos\Ai\Domain\Response\AiResponse;
use DressnMore\Aos\Ai\Domain\Retry\RetryManager;
use DressnMore\Aos\Ai\Domain\Selection\ProviderSelector;
use DressnMore\Aos\Ai\Domain\Streaming\StreamingEngine;
use DressnMore\Aos\Ai\Infrastructure\Bootstrap\BuiltinAiCatalogBootstrap;
use DressnMore\Aos\Events\Contracts\EventBusInterface;

/**
 * AI Engine — provider-agnostic completion/streaming; no HTTP / SDK.
 */
final class AiEngine implements AiEngineInterface
{
    public function __construct(
        private readonly CompletionEngine $completions,
        private readonly StreamingEngine $streaming,
        private readonly ProviderManager $manager,
        private readonly EventBusInterface $eventBus,
    ) {}

    public static function createDefault(EventBusInterface $eventBus): self
    {
        $providers = new ProviderRegistry();
        $models = new ModelRegistry();
        $factory = new ProviderFactory();
        $health = new ProviderHealthMonitor($providers);
        $metrics = new ProviderMetrics();
        $policies = new ProviderPolicyEngine();
        $manager = new ProviderManager($providers, $models, $factory, $health);
        (new BuiltinAiCatalogBootstrap($manager))->seed();

        $pipelineFactory = new AiPipelineFactory(
            new CapabilityRegistry(),
            new ProviderResolver($providers, $policies),
            new ModelResolver($models, $policies),
            new ProviderSelector($providers, $models, $policies),
            $policies,
            $health,
            $providers,
            new RetryManager(2),
            new FallbackManager(),
            $metrics,
            new CostManager(),
        );
        $pipeline = $pipelineFactory->create();

        return new self(
            new CompletionEngine($pipeline),
            new StreamingEngine($pipeline),
            $manager,
            $eventBus,
        );
    }

    public function complete(AiRequest $request): AiResponse
    {
        $this->eventBus->publish(new CompletionRequested($request->correlationId(), false));

        $nonStreaming = AiRequest::create(
            $request->prompt(),
            $request->conversation(),
            $request->context(),
            $request->requiredCapabilities(),
            $request->preferredProviderId(),
            $request->preferredModelId(),
            false,
            $request->temperature(),
            $request->maxTokens(),
            $request->tenantId(),
            $request->maxBudgetUsd(),
            $request->maxLatencyMs(),
            $request->metadata(),
            $request->correlationId(),
        );
        $bag = $this->completions->runBag($nonStreaming);

        if ($bag->selection() !== null) {
            $this->eventBus->publish(new ProviderSelected(
                $request->correlationId(),
                $bag->selection()->provider()->id()->toString(),
                $bag->selection()->model()->id()->toString(),
                $bag->selection()->score(),
            ));
        }

        if ($bag->fallbackUsed() && count($bag->rankedSelections()) >= 2) {
            $this->eventBus->publish(new FallbackActivated(
                $request->correlationId(),
                $bag->rankedSelections()[0]->provider()->id()->toString(),
                $bag->selection()?->provider()->id()->toString() ?? 'unknown',
            ));
        }

        foreach ($bag->rejectionNotes() as $note) {
            if (str_starts_with($note, 'no_provider') || str_contains($note, 'budget')) {
                $this->eventBus->publish(new BudgetExceeded(
                    $request->correlationId(),
                    $request->preferredModelId()?->toString() ?? 'n/a',
                    $request->maxBudgetUsd(),
                ));
            }
        }

        $response = $bag->response();
        if ($response === null) {
            throw new \RuntimeException('AI completion failed: '.implode(',', $bag->rejectionNotes()));
        }

        $this->eventBus->publish(new CompletionReceived(
            $request->correlationId(),
            $response->providerId()->toString(),
            $response->modelId()->toString(),
            $response->costUsd(),
            $response->latencyMs(),
        ));

        return $response;
    }

    public function stream(AiRequest $request): array
    {
        $this->eventBus->publish(new CompletionRequested($request->correlationId(), true));
        $this->eventBus->publish(new StreamingStarted($request->correlationId()));

        $result = $this->streaming->stream($request);

        $this->eventBus->publish(new StreamingCompleted(
            $request->correlationId(),
            count($result['chunks']),
        ));
        $this->eventBus->publish(new CompletionReceived(
            $request->correlationId(),
            $result['response']->providerId()->toString(),
            $result['response']->modelId()->toString(),
            $result['response']->costUsd(),
            $result['response']->latencyMs(),
        ));

        return $result;
    }

    public function manager(): ProviderManager
    {
        return $this->manager;
    }
}

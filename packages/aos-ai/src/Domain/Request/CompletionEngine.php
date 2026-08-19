<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Request;

use DressnMore\Aos\Ai\Domain\Pipeline\AiPipeline;
use DressnMore\Aos\Ai\Domain\Pipeline\AiPipelineBag;
use DressnMore\Aos\Ai\Domain\Response\AiResponse;
use RuntimeException;

final class CompletionEngine
{
    public function __construct(
        private readonly AiPipeline $pipeline,
    ) {}

    public function complete(AiRequest $request): AiResponse
    {
        $bag = $this->pipeline->process(new AiPipelineBag(
            $request->streaming() ? AiRequest::create(
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
            ) : $request
        ));

        $response = $bag->response();
        if ($response === null) {
            throw new RuntimeException('Completion pipeline produced no response.');
        }

        return $response;
    }

    public function runBag(AiRequest $request): AiPipelineBag
    {
        return $this->pipeline->process(new AiPipelineBag($request));
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Streaming;

use DressnMore\Aos\Ai\Domain\Pipeline\AiPipeline;
use DressnMore\Aos\Ai\Domain\Pipeline\AiPipelineBag;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;
use DressnMore\Aos\Ai\Domain\Response\AiResponse;
use RuntimeException;

final class StreamingEngine
{
    public function __construct(
        private readonly AiPipeline $pipeline,
    ) {}

    /**
     * @return array{response: AiResponse, chunks: list<StreamChunk>}
     */
    public function stream(AiRequest $request): array
    {
        $streamingRequest = AiRequest::create(
            $request->prompt(),
            $request->conversation(),
            $request->context(),
            $request->requiredCapabilities(),
            $request->preferredProviderId(),
            $request->preferredModelId(),
            true,
            $request->temperature(),
            $request->maxTokens(),
            $request->tenantId(),
            $request->maxBudgetUsd(),
            $request->maxLatencyMs(),
            $request->metadata(),
            $request->correlationId(),
        );

        $bag = $this->pipeline->process(new AiPipelineBag($streamingRequest));
        $response = $bag->response();
        if ($response === null) {
            throw new RuntimeException('Streaming pipeline produced no response.');
        }

        return [
            'response' => $response,
            'chunks' => $bag->streamChunks(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Contracts;

use DressnMore\Aos\Ai\Domain\Request\AiRequest;
use DressnMore\Aos\Ai\Domain\Response\AiResponse;
use DressnMore\Aos\Ai\Domain\Streaming\StreamChunk;

interface AiEngineInterface
{
    public function complete(AiRequest $request): AiResponse;

    /**
     * @return array{response: AiResponse, chunks: list<StreamChunk>}
     */
    public function stream(AiRequest $request): array;
}

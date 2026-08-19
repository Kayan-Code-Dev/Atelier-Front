<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Contracts;

use DressnMore\Aos\Ai\Domain\Request\AiRequest;
use DressnMore\Aos\Ai\Domain\Response\AiResponse;
use DressnMore\Aos\Ai\Domain\Streaming\StreamChunk;

/**
 * Plugin port for an AI provider — no HTTP / SDK inside Domain.
 * Adapters implement this; Sprint 10 ships stub adapters only.
 */
interface AiProviderInterface
{
    public function id(): string;

    public function complete(AiRequest $request): AiResponse;

    /**
     * @return iterable<int, StreamChunk>
     */
    public function stream(AiRequest $request): iterable;

    public function isAvailable(): bool;
}

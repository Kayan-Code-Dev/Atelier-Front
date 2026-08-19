<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Events;

final class StreamingCompleted extends AiDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly int $chunkCount,
    ) {
        parent::__construct($correlationId);
    }
}

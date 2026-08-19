<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Events;

final class CompletionReceived extends AiDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $providerId,
        public readonly string $modelId,
        public readonly float $costUsd,
        public readonly int $latencyMs,
    ) {
        parent::__construct($correlationId);
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Events;

final class ProviderSelected extends AiDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $providerId,
        public readonly string $modelId,
        public readonly float $score,
    ) {
        parent::__construct($correlationId);
    }
}

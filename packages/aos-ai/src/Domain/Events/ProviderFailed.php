<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Events;

final class ProviderFailed extends AiDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $providerId,
        public readonly string $reason,
    ) {
        parent::__construct($correlationId);
    }
}

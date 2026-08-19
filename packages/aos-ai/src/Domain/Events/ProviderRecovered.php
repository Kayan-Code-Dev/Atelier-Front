<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Events;

final class ProviderRecovered extends AiDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $providerId,
    ) {
        parent::__construct($correlationId);
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Events;

final class FallbackActivated extends AiDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $fromProviderId,
        public readonly string $toProviderId,
    ) {
        parent::__construct($correlationId);
    }
}

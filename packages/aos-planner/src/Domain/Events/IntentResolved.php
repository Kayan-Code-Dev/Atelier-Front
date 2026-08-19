<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Events;

final class IntentResolved extends PlannerDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $intentKind,
        public readonly float $confidence,
    ) {
        parent::__construct($correlationId);
    }
}

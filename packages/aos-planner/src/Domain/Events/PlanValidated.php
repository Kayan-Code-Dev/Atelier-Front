<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Events;

final class PlanValidated extends PlannerDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $planId,
        public readonly bool $valid,
        public readonly string $notes,
    ) {
        parent::__construct($correlationId);
    }
}

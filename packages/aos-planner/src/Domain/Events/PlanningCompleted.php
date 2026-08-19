<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Events;

final class PlanningCompleted extends PlannerDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $planId,
        public readonly string $decision,
    ) {
        parent::__construct($correlationId);
    }
}

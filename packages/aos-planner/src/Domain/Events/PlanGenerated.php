<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Events;

final class PlanGenerated extends PlannerDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $planId,
        public readonly int $taskCount,
    ) {
        parent::__construct($correlationId);
    }
}

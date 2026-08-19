<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Events;

final class PlanningFailed extends PlannerDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $reasonCode,
        public readonly string $message,
    ) {
        parent::__construct($correlationId);
    }
}

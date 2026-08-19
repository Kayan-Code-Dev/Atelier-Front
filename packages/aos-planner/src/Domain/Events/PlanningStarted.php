<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Events;

final class PlanningStarted extends PlannerDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $sessionId,
    ) {
        parent::__construct($correlationId);
    }
}

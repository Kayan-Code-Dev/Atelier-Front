<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Events;

final class ClarificationRequired extends PlannerDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $prompt,
    ) {
        parent::__construct($correlationId);
    }
}

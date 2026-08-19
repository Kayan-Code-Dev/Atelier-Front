<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Events;

final class GoalsResolved extends PlannerDomainEvent
{
    /**
     * @param  list<string>  $goalCodes
     */
    public function __construct(
        string $correlationId,
        public readonly array $goalCodes,
    ) {
        parent::__construct($correlationId);
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Events;

final class ToolSelected extends PlannerDomainEvent
{
    /**
     * @param list<string> $toolIds
     */
    public function __construct(
        string $correlationId,
        public readonly array $toolIds,
        public readonly int $stepCount,
    ) {
        parent::__construct($correlationId);
    }
}

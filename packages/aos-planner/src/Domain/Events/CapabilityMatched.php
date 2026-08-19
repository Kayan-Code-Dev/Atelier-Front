<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Events;

final class CapabilityMatched extends PlannerDomainEvent
{
    /**
     * @param list<string> $capabilities
     */
    public function __construct(
        string $correlationId,
        public readonly array $capabilities,
        public readonly bool $ok,
    ) {
        parent::__construct($correlationId);
    }
}

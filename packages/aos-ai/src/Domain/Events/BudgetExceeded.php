<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Events;

final class BudgetExceeded extends AiDomainEvent
{
    public function __construct(
        string $correlationId,
        public readonly string $modelId,
        public readonly float $maxBudgetUsd,
    ) {
        parent::__construct($correlationId);
    }
}

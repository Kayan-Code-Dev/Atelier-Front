<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Platform;

final class PlanStep
{
    public function __construct(
        private readonly int $order,
        private readonly string $toolName,
        private readonly ?string $capability = null,
        private readonly string $goal = '',
    ) {}

    public function order(): int { return $this->order; }
    public function toolName(): string { return $this->toolName; }
    public function capability(): ?string { return $this->capability; }
    public function goal(): string { return $this->goal; }
}

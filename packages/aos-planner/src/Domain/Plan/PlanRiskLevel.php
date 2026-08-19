<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Plan;

enum PlanRiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function atLeast(self $other): bool
    {
        return $this->rank() >= $other->rank();
    }

    public function rank(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Critical => 4,
        };
    }
}

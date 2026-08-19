<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Plan;

use DressnMore\Aos\Planner\Domain\Goal\PlanningGoal;
use DressnMore\Aos\Planner\Domain\Intent\IntentKind;
use DressnMore\Aos\Planner\Domain\Task\PlannedTask;
use InvalidArgumentException;
use Stringable;

final class ExecutionPlanId implements Stringable
{
    public function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('ExecutionPlanId cannot be empty.');
        }
    }

    public static function generate(): self
    {
        return new self('plan_'.bin2hex(random_bytes(8)));
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

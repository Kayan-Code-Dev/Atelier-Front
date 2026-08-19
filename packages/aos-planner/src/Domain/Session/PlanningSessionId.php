<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Session;

use DateTimeImmutable;
use DressnMore\Aos\Planner\Domain\Context\PlanningContext;
use DressnMore\Aos\Planner\Domain\Intent\IntentResolution;
use DressnMore\Aos\Planner\Domain\Plan\ExecutionPlan;
use InvalidArgumentException;
use Stringable;

final class PlanningSessionId implements Stringable
{
    public function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('PlanningSessionId cannot be empty.');
        }
    }

    public static function generate(): self
    {
        return new self('ps_'.bin2hex(random_bytes(8)));
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

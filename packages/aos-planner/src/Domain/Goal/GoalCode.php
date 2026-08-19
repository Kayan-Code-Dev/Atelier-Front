<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Goal;

use InvalidArgumentException;
use Stringable;

final class GoalCode implements Stringable
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim(str_replace([' ', '-'], '_', $value)));
        if ($normalized === '') {
            throw new InvalidArgumentException('GoalCode cannot be empty.');
        }
        $this->value = $normalized;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

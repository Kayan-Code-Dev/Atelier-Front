<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Intent;

use InvalidArgumentException;
use Stringable;

/**
 * Stable intent code (e.g. check_balance) — not an LLM label dump.
 */
final class IntentCode implements Stringable
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim(str_replace([' ', '-'], '_', $value)));
        if ($normalized === '') {
            throw new InvalidArgumentException('IntentCode cannot be empty.');
        }
        $this->value = $normalized;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function unknown(): self
    {
        return new self('unknown');
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

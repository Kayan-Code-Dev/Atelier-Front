<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Request;

use InvalidArgumentException;
use Stringable;

/**
 * Who requested the tool (planner, human, system) — opaque label.
 */
final class RequestedBy implements Stringable
{
    public function __construct(
        private readonly string $value,
    ) {
        if (trim($this->value) === '') {
            throw new InvalidArgumentException('RequestedBy cannot be empty.');
        }
    }

    public static function planner(): self
    {
        return new self('planner');
    }

    public static function system(): self
    {
        return new self('system');
    }

    public static function human(string $actorId = 'human'): self
    {
        return new self($actorId);
    }

    public static function of(string $value): self
    {
        return new self($value);
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

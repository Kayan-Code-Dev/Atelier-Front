<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Tool;

use InvalidArgumentException;
use Stringable;

final class ToolVersion implements Stringable
{
    public function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('ToolVersion cannot be empty.');
        }
    }

    public static function of(string $value): self
    {
        return new self($value);
    }

    public static function v1(): self
    {
        return new self('1.0.0');
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

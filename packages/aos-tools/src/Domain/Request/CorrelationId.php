<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Request;

use InvalidArgumentException;
use Stringable;

final class CorrelationId implements Stringable
{
    public function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('CorrelationId cannot be empty.');
        }
    }

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(16)));
    }

    public static function fromString(string $value): self
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

<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Domain\Customer;

use InvalidArgumentException;
use Stringable;

final class CustomerId implements Stringable
{
    public function __construct(private readonly string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('CustomerId cannot be empty.');
        }
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

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Tenant;

use InvalidArgumentException;
use Stringable;

final class TenantId implements Stringable
{
    public function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('TenantId cannot be empty.');
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

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

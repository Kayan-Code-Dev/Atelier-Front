<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Capability;

use InvalidArgumentException;
use Stringable;

final class CapabilityCode implements Stringable
{
    private function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('CapabilityCode cannot be empty.');
        }
    }

    public static function fromBuiltin(BuiltinCapability $capability): self
    {
        return new self($capability->value);
    }

    public static function custom(string $value): self
    {
        $normalized = strtolower(trim(str_replace([' ', '-'], '_', $value)));
        if ($normalized === '') {
            throw new InvalidArgumentException('Custom capability cannot be empty.');
        }

        return new self($normalized);
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim(str_replace([' ', '-'], '_', $value)));
        $builtin = BuiltinCapability::tryFrom($normalized);

        return $builtin !== null ? self::fromBuiltin($builtin) : self::custom($normalized);
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

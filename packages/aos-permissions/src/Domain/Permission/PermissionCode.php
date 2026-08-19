<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Permission;

use InvalidArgumentException;
use Stringable;

final class PermissionCode implements Stringable
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            throw new InvalidArgumentException('PermissionCode cannot be empty.');
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

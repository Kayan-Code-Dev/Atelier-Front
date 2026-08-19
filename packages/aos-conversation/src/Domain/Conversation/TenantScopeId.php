<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Conversation;

use InvalidArgumentException;
use Stringable;

/**
 * Opaque tenant scope token for conversation isolation (no Tenant Ops coupling).
 */
final class TenantScopeId implements Stringable
{
    public function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('TenantScopeId cannot be empty.');
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

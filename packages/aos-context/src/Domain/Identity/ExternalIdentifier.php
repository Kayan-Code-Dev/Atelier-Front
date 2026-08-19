<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Identity;

use InvalidArgumentException;
use Stringable;

/**
 * Opaque external identifier on a channel (phone, PSID, email, app user id, …).
 */
final class ExternalIdentifier implements Stringable
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);
        if ($normalized === '') {
            throw new InvalidArgumentException('ExternalIdentifier cannot be empty.');
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

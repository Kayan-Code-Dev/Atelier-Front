<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Tool;

use InvalidArgumentException;
use Stringable;

/**
 * Stable tool contract identity (e.g. GetOrderStatus) — not a PHP class name.
 */
final class ToolIdentifier implements Stringable
{
    public function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '' || ! preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $this->value)) {
            throw new InvalidArgumentException('ToolIdentifier must be PascalCase alphanumeric.');
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

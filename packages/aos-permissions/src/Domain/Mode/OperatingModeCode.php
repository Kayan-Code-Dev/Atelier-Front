<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Mode;

use InvalidArgumentException;
use Stringable;

/**
 * Extensible operating mode code (builtins + future custom modes).
 */
final class OperatingModeCode implements Stringable
{
    private function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('OperatingModeCode cannot be empty.');
        }
    }

    public static function fromEnum(OperatingMode $mode): self
    {
        return new self($mode->value);
    }

    public static function custom(string $value): self
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            throw new InvalidArgumentException('Custom operating mode cannot be empty.');
        }

        return new self($normalized);
    }

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));
        $builtin = OperatingMode::tryFrom($normalized);

        return $builtin !== null ? self::fromEnum($builtin) : self::custom($normalized);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function isBuiltin(): bool
    {
        return OperatingMode::tryFrom($this->value) !== null;
    }

    public function toBuiltin(): ?OperatingMode
    {
        return OperatingMode::tryFrom($this->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

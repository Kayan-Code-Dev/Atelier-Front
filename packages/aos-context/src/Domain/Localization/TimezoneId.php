<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Localization;

use InvalidArgumentException;

final class TimezoneId
{
    public function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('TimezoneId cannot be empty.');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function utc(): self
    {
        return new self('UTC');
    }

    public function toString(): string
    {
        return $this->value;
    }
}

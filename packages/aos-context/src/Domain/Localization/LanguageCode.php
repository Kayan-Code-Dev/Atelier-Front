<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Localization;

use InvalidArgumentException;

final class LanguageCode
{
    public function __construct(
        private readonly string $value,
    ) {
        if (! preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $this->value)) {
            throw new InvalidArgumentException('Invalid LanguageCode: '.$this->value);
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function arabic(): self
    {
        return new self('ar');
    }

    public static function english(): self
    {
        return new self('en');
    }

    public function toString(): string
    {
        return $this->value;
    }
}

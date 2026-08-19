<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Persona;

use InvalidArgumentException;
use Stringable;

final class PersonaId implements Stringable
{
    public function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('PersonaId cannot be empty.');
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

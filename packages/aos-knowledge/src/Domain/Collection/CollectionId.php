<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Collection;

use InvalidArgumentException;
use Stringable;

final class CollectionId implements Stringable
{
    public function __construct(private readonly string $value)
    {
        if ($this->value === '') {
            throw new InvalidArgumentException('CollectionId cannot be empty.');
        }
    }

    public static function generate(): self
    {
        return new self('col_'.bin2hex(random_bytes(6)));
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

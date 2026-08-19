<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Knowledge;

use InvalidArgumentException;
use Stringable;

final class KnowledgeId implements Stringable
{
    public function __construct(private readonly string $value)
    {
        if ($this->value === '') {
            throw new InvalidArgumentException('KnowledgeId cannot be empty.');
        }
    }

    public static function generate(): self
    {
        return new self('knw_'.bin2hex(random_bytes(8)));
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

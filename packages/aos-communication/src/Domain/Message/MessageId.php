<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Message;

use InvalidArgumentException;
use Stringable;

final class MessageId implements Stringable
{
    public function __construct(private readonly string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('MessageId cannot be empty.');
        }
    }

    public static function generate(): self
    {
        return new self('msg_'.bin2hex(random_bytes(8)));
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

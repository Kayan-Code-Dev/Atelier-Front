<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Message;

use InvalidArgumentException;
use Stringable;

final class MessageId implements Stringable
{
    public function __construct(
        private readonly string $value,
    ) {
        if ($this->value === '') {
            throw new InvalidArgumentException('MessageId cannot be empty.');
        }
    }

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(16)));
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

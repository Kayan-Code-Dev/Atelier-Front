<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Message;

use InvalidArgumentException;

/**
 * Textual message body (channel-agnostic).
 */
final class MessageContent
{
    public function __construct(
        private readonly string $text,
    ) {
        if (trim($this->text) === '') {
            throw new InvalidArgumentException('MessageContent cannot be empty.');
        }
    }

    public function text(): string
    {
        return $this->text;
    }
}

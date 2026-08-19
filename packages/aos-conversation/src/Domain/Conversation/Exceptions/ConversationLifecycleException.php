<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Conversation\Exceptions;

use DomainException;

/**
 * Raised when a lifecycle operation is not valid for the current aggregate state.
 */
final class ConversationLifecycleException extends DomainException
{
    public static function cannotMessage(string $reason): self
    {
        return new self('Cannot add message: '.$reason);
    }

    public static function noOpenSession(): self
    {
        return new self('No open conversation session.');
    }

    public static function sessionAlreadyOpen(): self
    {
        return new self('A conversation session is already open.');
    }

    public static function cannotStartSession(string $reason): self
    {
        return new self('Cannot start session: '.$reason);
    }
}

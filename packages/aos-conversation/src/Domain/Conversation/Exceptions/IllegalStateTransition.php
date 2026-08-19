<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Conversation\Exceptions;

use DomainException;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationStatus;

/**
 * Raised when a state transition is not allowed by the state machine.
 */
final class IllegalStateTransition extends DomainException
{
    public static function between(ConversationStatus $from, ConversationStatus $to): self
    {
        return new self(sprintf(
            'Illegal conversation state transition from [%s] to [%s].',
            $from->value,
            $to->value
        ));
    }
}

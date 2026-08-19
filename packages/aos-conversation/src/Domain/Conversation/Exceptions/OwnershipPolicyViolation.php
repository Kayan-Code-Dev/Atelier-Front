<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Conversation\Exceptions;

use DomainException;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationOwnership;

/**
 * Raised when an ownership change violates policy.
 */
final class OwnershipPolicyViolation extends DomainException
{
    public static function cannotAssign(ConversationOwnership $from, ConversationOwnership $to, string $reason): self
    {
        return new self(sprintf(
            'Ownership change from [%s] to [%s] denied: %s',
            $from->value,
            $to->value,
            $reason
        ));
    }
}

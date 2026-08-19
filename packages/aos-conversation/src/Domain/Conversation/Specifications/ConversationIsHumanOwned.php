<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Conversation\Specifications;

use DressnMore\Aos\Conversation\Domain\Conversation\Conversation;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationOwnership;

/**
 * True when a human currently owns (or co-owns) the conversation.
 */
final class ConversationIsHumanOwned
{
    public function isSatisfiedBy(Conversation $conversation): bool
    {
        return in_array(
            $conversation->ownership(),
            [ConversationOwnership::Human, ConversationOwnership::SharedAssist],
            true
        );
    }
}

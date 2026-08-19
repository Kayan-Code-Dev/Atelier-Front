<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Conversation\Specifications;

use DressnMore\Aos\Conversation\Domain\Conversation\Conversation;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationStatus;

/**
 * True when the conversation may accept new messages.
 */
final class ConversationIsOpenForMessaging
{
    public function isSatisfiedBy(Conversation $conversation): bool
    {
        $status = $conversation->status();

        return ! $status->isTerminal()
            && $status !== ConversationStatus::Paused
            && $status !== ConversationStatus::New;
    }
}

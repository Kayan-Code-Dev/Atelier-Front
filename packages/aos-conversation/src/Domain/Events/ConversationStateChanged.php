<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Events;

use DressnMore\Aos\Conversation\Domain\Conversation\ConversationId;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationStatus;

final class ConversationStateChanged extends ConversationDomainEvent
{
    public function __construct(
        ConversationId $conversationId,
        public readonly ConversationStatus $from,
        public readonly ConversationStatus $to,
    ) {
        parent::__construct($conversationId);
    }
}

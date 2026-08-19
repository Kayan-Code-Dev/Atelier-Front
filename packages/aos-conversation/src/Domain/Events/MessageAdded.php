<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Events;

use DressnMore\Aos\Conversation\Domain\Conversation\ConversationId;
use DressnMore\Aos\Conversation\Domain\Message\MessageId;

final class MessageAdded extends ConversationDomainEvent
{
    public function __construct(
        ConversationId $conversationId,
        public readonly MessageId $messageId,
    ) {
        parent::__construct($conversationId);
    }
}

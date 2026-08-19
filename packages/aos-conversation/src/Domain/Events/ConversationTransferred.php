<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Events;

use DressnMore\Aos\Conversation\Domain\Conversation\ConversationId;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationOwnership;

final class ConversationTransferred extends ConversationDomainEvent
{
    public function __construct(
        ConversationId $conversationId,
        public readonly ConversationOwnership $from,
        public readonly ConversationOwnership $to,
    ) {
        parent::__construct($conversationId);
    }
}

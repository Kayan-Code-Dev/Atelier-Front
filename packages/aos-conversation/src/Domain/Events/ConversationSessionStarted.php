<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Events;

use DressnMore\Aos\Conversation\Domain\Conversation\ConversationId;
use DressnMore\Aos\Conversation\Domain\Session\SessionId;

final class ConversationSessionStarted extends ConversationDomainEvent
{
    public function __construct(
        ConversationId $conversationId,
        public readonly SessionId $sessionId,
    ) {
        parent::__construct($conversationId);
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Events;

use DressnMore\Aos\Conversation\Domain\Conversation\ConversationId;
use DressnMore\Aos\Conversation\Domain\Conversation\ConversationOwnership;
use DressnMore\Aos\Conversation\Domain\Conversation\TenantScopeId;

final class ConversationStarted extends ConversationDomainEvent
{
    public function __construct(
        ConversationId $conversationId,
        public readonly TenantScopeId $tenantScopeId,
        public readonly ConversationOwnership $ownership,
    ) {
        parent::__construct($conversationId);
    }
}

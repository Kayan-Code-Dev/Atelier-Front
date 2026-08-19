<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Conversation;

enum ConversationStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Archived = 'archived';
}

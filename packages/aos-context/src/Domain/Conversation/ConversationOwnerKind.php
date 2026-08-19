<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Conversation;

/**
 * Current conversation owner as known to Context (opaque labels).
 */
enum ConversationOwnerKind: string
{
    case AI = 'ai';
    case Human = 'human';
    case SharedAssist = 'shared_assist';
    case System = 'system';
    case Unknown = 'unknown';
}

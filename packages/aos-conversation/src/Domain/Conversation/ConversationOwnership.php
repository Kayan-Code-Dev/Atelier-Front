<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Conversation;

/**
 * Single owner of a Conversation at any moment.
 */
enum ConversationOwnership: string
{
    case AI = 'ai';
    case Human = 'human';
    case SharedAssist = 'shared_assist';
    case System = 'system';
}

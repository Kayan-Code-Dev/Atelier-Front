<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Conversation;

/**
 * Lifecycle states for the Conversation aggregate (Sprint 2).
 */
enum ConversationStatus: string
{
    case New = 'new';
    case Active = 'active';
    case WaitingCustomer = 'waiting_customer';
    case WaitingHuman = 'waiting_human';
    case HumanHandling = 'human_handling';
    case Paused = 'paused';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Archived = 'archived';

    public function isTerminal(): bool
    {
        return $this === self::Closed || $this === self::Archived;
    }
}

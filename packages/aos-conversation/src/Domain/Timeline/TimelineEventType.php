<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Timeline;

enum TimelineEventType: string
{
    case ConversationStarted = 'conversation_started';
    case OwnerChanged = 'owner_changed';
    case MessageAdded = 'message_added';
    case StateChanged = 'state_changed';
    case SummaryGenerated = 'summary_generated';
    case Escalated = 'escalated';
    case ReturnedToAi = 'returned_to_ai';
    case Closed = 'closed';
    case SessionStarted = 'session_started';
    case SessionEnded = 'session_ended';
    case Paused = 'paused';
    case Resumed = 'resumed';
    case Archived = 'archived';
}

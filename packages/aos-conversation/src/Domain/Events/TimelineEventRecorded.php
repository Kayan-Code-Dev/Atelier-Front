<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Events;

use DressnMore\Aos\Conversation\Domain\Conversation\ConversationId;
use DressnMore\Aos\Conversation\Domain\Timeline\TimelineEventType;

final class TimelineEventRecorded extends ConversationDomainEvent
{
    public function __construct(
        ConversationId $conversationId,
        public readonly string $timelineEventId,
        public readonly TimelineEventType $type,
    ) {
        parent::__construct($conversationId);
    }
}

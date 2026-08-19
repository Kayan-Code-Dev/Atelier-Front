<?php

declare(strict_types=1);

namespace DressnMore\Aos\Conversation\Domain\Events;

use DressnMore\Aos\Conversation\Domain\Conversation\ConversationId;
use DressnMore\Aos\Events\AbstractEvent;
use DressnMore\Aos\Events\Markers\DomainEventMarker;

abstract class ConversationDomainEvent extends AbstractEvent implements DomainEventMarker
{
    public function __construct(
        public readonly ConversationId $conversationId,
    ) {
        parent::__construct();
    }
}

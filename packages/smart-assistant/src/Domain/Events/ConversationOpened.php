<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Events;

final class ConversationOpened extends SmartAssistantDomainEvent
{
    public function __construct(string $tenantId, public readonly string $conversationId, string $correlationId = '')
    {
        parent::__construct($tenantId, $correlationId);
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Events;

final class AgentDeactivated extends SmartAssistantDomainEvent
{
    public function __construct(string $tenantId, public readonly string $agentId, string $correlationId = '')
    {
        parent::__construct($tenantId, $correlationId);
    }
}

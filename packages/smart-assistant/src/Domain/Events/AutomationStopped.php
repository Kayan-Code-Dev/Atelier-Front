<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Events;

final class AutomationStopped extends SmartAssistantDomainEvent
{
    public function __construct(string $tenantId, public readonly string $automationId, string $correlationId = '')
    {
        parent::__construct($tenantId, $correlationId);
    }
}

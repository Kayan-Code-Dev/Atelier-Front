<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Agent;

interface AgentResolverInterface
{
    public function resolve(string $tenantId, string $intentHint = ''): ?AgentInterface;
}

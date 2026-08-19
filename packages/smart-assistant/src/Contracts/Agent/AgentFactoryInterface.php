<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Agent;

interface AgentFactoryInterface
{
    public function make(string $agentType): AgentInterface;
}

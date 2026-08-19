<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Registry;

use DressnMore\SmartAssistant\Contracts\Agent\AgentInterface;

interface AgentRegistryInterface
{
    public function register(AgentInterface $agent): void;

    public function get(string $agentId): ?AgentInterface;

    public function has(string $agentId): bool;

    /**
     * @return list<AgentInterface>
     */
    public function all(): array;
}

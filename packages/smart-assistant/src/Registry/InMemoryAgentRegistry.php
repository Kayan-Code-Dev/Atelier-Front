<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Registry;

use DressnMore\SmartAssistant\Contracts\Agent\AgentInterface;
use DressnMore\SmartAssistant\Contracts\Registry\AgentRegistryInterface;
use LogicException;

/** In-memory registry for architecture validation only. */
final class InMemoryAgentRegistry implements AgentRegistryInterface
{
    /** @var array<string, AgentInterface> */
    private array $items = [];

    public function register(AgentInterface $agent): void
    {
        $id = $agent->identity()->id();
        if (isset($this->items[$id])) {
            throw new LogicException('Agent already registered: '.$id);
        }
        $this->items[$id] = $agent;
    }

    public function get(string $agentId): ?AgentInterface
    {
        return $this->items[$agentId] ?? null;
    }

    public function has(string $agentId): bool
    {
        return isset($this->items[$agentId]);
    }

    public function all(): array
    {
        return array_values($this->items);
    }
}

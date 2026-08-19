<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Registry;

use DressnMore\SmartAssistant\Contracts\Registry\CapabilityRegistryInterface;
use DressnMore\SmartAssistant\Domain\Agent\Capability;

final class InMemoryCapabilityRegistry implements CapabilityRegistryInterface
{
    /** @var array<string, Capability> */
    private array $items = [];

    public function register(Capability $capability): void
    {
        $this->items[$capability->id()] = $capability;
    }

    public function get(string $capabilityId): ?Capability
    {
        return $this->items[$capabilityId] ?? null;
    }

    public function forAgentType(string $agentType): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (Capability $c): bool => $c->agentType() === $agentType
        ));
    }

    public function all(): array
    {
        return array_values($this->items);
    }
}

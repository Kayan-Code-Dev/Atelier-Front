<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Registry;

use DressnMore\SmartAssistant\Domain\Agent\Capability;

interface CapabilityRegistryInterface
{
    public function register(Capability $capability): void;

    public function get(string $capabilityId): ?Capability;

    /**
     * @return list<Capability>
     */
    public function forAgentType(string $agentType): array;

    /**
     * @return list<Capability>
     */
    public function all(): array;
}

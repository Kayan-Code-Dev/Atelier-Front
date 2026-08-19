<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Capability;

interface CapabilityRegistryInterface
{
    public function register(CapabilityDefinition $definition): void;

    public function get(CapabilityCode $code): ?CapabilityDefinition;

    public function has(CapabilityCode $code): bool;

    /**
     * @return list<CapabilityDefinition>
     */
    public function all(): array;
}

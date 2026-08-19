<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Contracts;

use DressnMore\Aos\ToolRegistry\Domain\Capability\CapabilityDescriptor;

interface CapabilityRegistryInterface
{
    public function register(CapabilityDescriptor $descriptor): void;

    public function has(string $capability): bool;

    public function get(string $capability): ?CapabilityDescriptor;

    /**
     * @return list<CapabilityDescriptor>
     */
    public function all(): array;

    /**
     * @return list<CapabilityDescriptor>
     */
    public function byOwnerDomain(string $ownerDomain): array;
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Capability;

final class CapabilityRegistry implements CapabilityRegistryInterface
{
    /** @var array<string, CapabilityDefinition> */
    private array $items = [];

    public function register(CapabilityDefinition $definition): void
    {
        $this->items[$definition->code()->toString()] = $definition;
    }

    public function get(CapabilityCode $code): ?CapabilityDefinition
    {
        return $this->items[$code->toString()] ?? null;
    }

    public function has(CapabilityCode $code): bool
    {
        return isset($this->items[$code->toString()]);
    }

    public function all(): array
    {
        return array_values($this->items);
    }
}

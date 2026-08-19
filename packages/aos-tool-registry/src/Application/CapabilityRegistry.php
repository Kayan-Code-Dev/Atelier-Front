<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\CapabilityRegistryInterface;
use DressnMore\Aos\ToolRegistry\Domain\Capability\CapabilityDescriptor;
use InvalidArgumentException;

final class CapabilityRegistry implements CapabilityRegistryInterface
{
    /** @var array<string, CapabilityDescriptor> */
    private array $capabilities = [];

    public function register(CapabilityDescriptor $descriptor): void
    {
        if ($descriptor->name() === '') {
            throw new InvalidArgumentException('Capability name cannot be empty.');
        }
        $this->capabilities[$descriptor->name()] = $descriptor;
    }

    public function has(string $capability): bool
    {
        return isset($this->capabilities[$capability]);
    }

    public function get(string $capability): ?CapabilityDescriptor
    {
        return $this->capabilities[$capability] ?? null;
    }

    public function all(): array
    {
        return array_values($this->capabilities);
    }

    public function byOwnerDomain(string $ownerDomain): array
    {
        return array_values(array_filter(
            $this->capabilities,
            static fn (CapabilityDescriptor $c): bool => $c->ownerDomain() === $ownerDomain,
        ));
    }
}

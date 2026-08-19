<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Application;

use DressnMore\Aos\ToolRegistry\Contracts\ProviderRegistryInterface;
use DressnMore\Aos\ToolRegistry\Domain\Provider\ProviderDescriptor;

final class ProviderRegistry implements ProviderRegistryInterface
{
    /** @var array<string, ProviderDescriptor> */
    private array $providers = [];

    public function register(ProviderDescriptor $descriptor): void
    {
        $this->providers[$descriptor->id()] = $descriptor;
    }

    public function get(string $id): ?ProviderDescriptor
    {
        return $this->providers[$id] ?? null;
    }

    public function all(): array
    {
        return array_values($this->providers);
    }
}

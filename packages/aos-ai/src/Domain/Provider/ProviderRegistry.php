<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Provider;

use DressnMore\Aos\Ai\Contracts\AiProviderInterface;

final class ProviderRegistry implements ProviderRegistryInterface
{
    /** @var array<string, ProviderDescriptor> */
    private array $descriptors = [];

    /** @var array<string, AiProviderInterface> */
    private array $plugins = [];

    public function register(ProviderDescriptor $descriptor, AiProviderInterface $plugin): void
    {
        $id = $descriptor->id()->toString();
        $this->descriptors[$id] = $descriptor;
        $this->plugins[$id] = $plugin;
    }

    public function get(ProviderId $id): ?ProviderDescriptor
    {
        return $this->descriptors[$id->toString()] ?? null;
    }

    public function plugin(ProviderId $id): ?AiProviderInterface
    {
        return $this->plugins[$id->toString()] ?? null;
    }

    public function all(): array
    {
        return array_values($this->descriptors);
    }

    public function update(ProviderDescriptor $descriptor): void
    {
        $id = $descriptor->id()->toString();
        if (! isset($this->descriptors[$id])) {
            return;
        }
        $this->descriptors[$id] = $descriptor;
    }
}

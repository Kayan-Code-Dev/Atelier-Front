<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Registry;

use DressnMore\SmartAssistant\Contracts\Registry\IntegrationRegistryInterface;
use DressnMore\SmartAssistant\Domain\Integration\IntegrationProvider;

final class InMemoryIntegrationRegistry implements IntegrationRegistryInterface
{
    /** @var array<string, IntegrationProvider> */
    private array $items = [];

    public function register(IntegrationProvider $provider): void
    {
        $this->items[$provider->id()] = $provider;
    }

    public function get(string $providerId): ?IntegrationProvider
    {
        return $this->items[$providerId] ?? null;
    }

    public function all(): array
    {
        return array_values($this->items);
    }
}

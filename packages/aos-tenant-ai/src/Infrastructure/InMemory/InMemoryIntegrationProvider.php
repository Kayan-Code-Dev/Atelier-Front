<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Infrastructure\InMemory;

use DressnMore\Aos\TenantAi\Contracts\IntegrationProviderInterface;
use DressnMore\Aos\TenantAi\Domain\Integration\IntegrationChannel;
use DressnMore\Aos\TenantAi\Domain\Integration\TenantIntegrationBinding;
use RuntimeException;

/** Test/demo only — registry, not live adapters. */
final class InMemoryIntegrationProvider implements IntegrationProviderInterface
{
    /** @var array<string, TenantIntegrationBinding> */
    private array $items = [];

    private function key(string $tenantId, IntegrationChannel $channel): string
    {
        return $tenantId.':'.$channel->value;
    }

    public function register(TenantIntegrationBinding $binding): TenantIntegrationBinding
    {
        $this->items[$this->key($binding->tenantId(), $binding->channel())] = $binding;

        return $binding;
    }

    public function find(string $tenantId, IntegrationChannel $channel): ?TenantIntegrationBinding
    {
        return $this->items[$this->key($tenantId, $channel)] ?? null;
    }

    public function listForTenant(string $tenantId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (TenantIntegrationBinding $b): bool => $b->tenantId() === $tenantId,
        ));
    }

    public function enable(string $tenantId, IntegrationChannel $channel): TenantIntegrationBinding
    {
        $current = $this->find($tenantId, $channel)
            ?? new TenantIntegrationBinding('int_'.bin2hex(random_bytes(4)), $tenantId, $channel, false);
        $enabled = new TenantIntegrationBinding($current->bindingId(), $tenantId, $channel, true, $current->metadata());

        return $this->register($enabled);
    }

    public function disable(string $tenantId, IntegrationChannel $channel): TenantIntegrationBinding
    {
        $current = $this->find($tenantId, $channel);
        if ($current === null) {
            throw new RuntimeException('Integration not enabled: '.$channel->value);
        }
        $disabled = new TenantIntegrationBinding($current->bindingId(), $tenantId, $channel, false, $current->metadata());

        return $this->register($disabled);
    }
}

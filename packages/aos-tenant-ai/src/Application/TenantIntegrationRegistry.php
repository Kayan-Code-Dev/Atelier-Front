<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Application;

use DressnMore\Aos\TenantAi\Contracts\IntegrationProviderInterface;
use DressnMore\Aos\TenantAi\Domain\Integration\IntegrationChannel;
use DressnMore\Aos\TenantAi\Domain\Integration\TenantIntegrationBinding;
use DressnMore\Aos\TenantAi\Domain\Policies\TenantAiPolicies;

/**
 * Registers/manages integration bindings only — does not execute channel adapters.
 */
final class TenantIntegrationRegistry
{
    public function __construct(
        private readonly IntegrationProviderInterface $integrations,
        private readonly TenantAiPolicies $policies = new TenantAiPolicies(),
    ) {}

    public function register(TenantIntegrationBinding $binding): TenantIntegrationBinding
    {
        return $this->integrations->register($binding);
    }

    public function enable(string $tenantId, IntegrationChannel $channel): TenantIntegrationBinding
    {
        return $this->integrations->enable($tenantId, $channel);
    }

    public function disable(string $tenantId, IntegrationChannel $channel): TenantIntegrationBinding
    {
        return $this->integrations->disable($tenantId, $channel);
    }

    public function assertEnabled(string $tenantId, IntegrationChannel $channel): TenantIntegrationBinding
    {
        $binding = $this->integrations->find($tenantId, $channel);
        if ($binding === null) {
            throw new \RuntimeException('Integration not enabled: '.$channel->value);
        }
        $this->policies->assertIntegrationVisible($binding, true);

        return $binding;
    }

    /**
     * @return list<TenantIntegrationBinding>
     */
    public function list(string $tenantId): array
    {
        return $this->integrations->listForTenant($tenantId);
    }
}

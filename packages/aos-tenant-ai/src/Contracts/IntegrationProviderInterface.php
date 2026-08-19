<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Contracts;

use DressnMore\Aos\TenantAi\Domain\Integration\IntegrationChannel;
use DressnMore\Aos\TenantAi\Domain\Integration\TenantIntegrationBinding;

interface IntegrationProviderInterface
{
    public function register(TenantIntegrationBinding $binding): TenantIntegrationBinding;

    public function find(string $tenantId, IntegrationChannel $channel): ?TenantIntegrationBinding;

    /**
     * @return list<TenantIntegrationBinding>
     */
    public function listForTenant(string $tenantId): array;

    public function enable(string $tenantId, IntegrationChannel $channel): TenantIntegrationBinding;

    public function disable(string $tenantId, IntegrationChannel $channel): TenantIntegrationBinding;
}

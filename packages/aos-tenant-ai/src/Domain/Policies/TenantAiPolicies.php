<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Policies;

use DressnMore\Aos\TenantAi\Domain\Conversation\AiConversation;
use DressnMore\Aos\TenantAi\Domain\Integration\TenantIntegrationBinding;
use DressnMore\Aos\TenantAi\Domain\Permission\PermissionResolution;
use DressnMore\Aos\TenantAi\Domain\Subscription\SubscriptionEntitlement;
use RuntimeException;

/**
 * Isolation & access policies — no persistence, pure guards.
 */
final class TenantAiPolicies
{
    public function assertTenantExists(?string $tenantId): void
    {
        if ($tenantId === null || $tenantId === '') {
            throw new RuntimeException('Tenant not found.');
        }
    }

    public function assertWorkspaceExists(bool $exists): void
    {
        if (! $exists) {
            throw new RuntimeException('Workspace not found.');
        }
    }

    public function assertConversationExists(bool $exists): void
    {
        if (! $exists) {
            throw new RuntimeException('Conversation not found.');
        }
    }

    public function assertTenantIsolation(string $actorTenantId, string $resourceTenantId): void
    {
        if ($actorTenantId !== $resourceTenantId) {
            throw new RuntimeException('Tenant isolation violation: cross-tenant access denied.');
        }
    }

    public function assertConversationIsolation(string $tenantId, AiConversation $conversation): void
    {
        if (! $conversation->belongsToTenant($tenantId)) {
            throw new RuntimeException('Conversation does not belong to the current tenant.');
        }
    }

    public function assertPermission(PermissionResolution $resolution, string $toolName): void
    {
        if (! $resolution->allowsTool($toolName)) {
            throw new RuntimeException('Permission denied: user cannot access tool '.$toolName);
        }
    }

    public function assertSubscription(SubscriptionEntitlement $entitlement, string $toolName): void
    {
        if (! $entitlement->allowsTool($toolName)) {
            throw new RuntimeException('Subscription denied: tool not available in plan '.$entitlement->plan()->value);
        }
    }

    public function assertToolVisibility(PermissionResolution $permissions, SubscriptionEntitlement $subscription, string $toolName): void
    {
        $this->assertPermission($permissions, $toolName);
        $this->assertSubscription($subscription, $toolName);
    }

    public function assertIntegrationVisible(TenantIntegrationBinding $binding, bool $featureEnabled): void
    {
        if (! $featureEnabled || ! $binding->enabled()) {
            throw new RuntimeException('Integration not enabled: '.$binding->channel()->value);
        }
    }
}

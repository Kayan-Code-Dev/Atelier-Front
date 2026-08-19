<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Application;

use DressnMore\Aos\TenantAi\Domain\Dashboard\AiDashboardMenu;
use DressnMore\Aos\TenantAi\Domain\Permission\PermissionResolution;
use DressnMore\Aos\TenantAi\Domain\Policies\TenantAiPolicies;
use DressnMore\Aos\TenantAi\Domain\Subscription\SubscriptionEntitlement;

/**
 * Guards tool access using permission ∩ subscription before Planner/Gateway.
 */
final class ToolAccessGuard
{
    public function __construct(private readonly TenantAiPolicies $policies = new TenantAiPolicies()) {}

    public function assertAllowed(PermissionResolution $permissions, SubscriptionEntitlement $subscription, string $toolName): void
    {
        $this->policies->assertToolVisibility($permissions, $subscription, $toolName);
    }

    /**
     * @return list<array{key:string,label:string,path:string}>
     */
    public function dashboardMenu(): array
    {
        return AiDashboardMenu::items();
    }
}

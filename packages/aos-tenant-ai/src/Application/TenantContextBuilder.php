<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Application;

use DressnMore\Aos\TenantAi\Contracts\ContextProviderInterface;
use DressnMore\Aos\TenantAi\Contracts\WorkspaceProviderInterface;
use DressnMore\Aos\TenantAi\Domain\Context\TenantAiContext;
use DressnMore\Aos\TenantAi\Domain\Session\AiSession;
use DressnMore\Aos\TenantAi\Domain\Subscription\SubscriptionPlan;

/**
 * Builds metadata-only context (no business entity loading).
 */
final class TenantContextBuilder implements ContextProviderInterface
{
    public function __construct(private readonly WorkspaceProviderInterface $workspaces) {}

    /**
     * @param list<string> $permissions
     */
    public function build(AiSession $session, string $role, array $permissions = [], ?string $country = null): TenantAiContext
    {
        $workspace = $this->workspaces->find($session->tenantId());
        $plan = $workspace?->subscriptionPlan() ?? SubscriptionPlan::Basic->value;

        return new TenantAiContext(
            $session->tenantId(),
            $session->branchId(),
            $session->userId(),
            $role,
            $permissions,
            $plan,
            $workspace?->language() ?? 'ar',
            $workspace?->currency() ?? 'SAR',
            $country,
            $workspace?->timezone() ?? 'Asia/Riyadh',
        );
    }
}

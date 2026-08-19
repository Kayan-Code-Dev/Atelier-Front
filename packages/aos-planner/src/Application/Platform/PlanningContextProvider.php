<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Application\Platform;

use DressnMore\Aos\Planner\Contracts\PlanningContextProviderInterface;
use DressnMore\Aos\Planner\Domain\Platform\PlatformPlanningContext;

final class PlanningContextProvider implements PlanningContextProviderInterface
{
    public function build(
        string $message,
        string $tenantId,
        ?string $conversationId = null,
        ?string $userId = null,
        ?string $branchId = null,
        string $language = 'ar',
        string $subscriptionPlan = 'basic',
        array $permissions = [],
        array $grantedCapabilities = [],
        array $grantedTools = [],
        array $availableTools = [],
        array $availableCapabilities = [],
    ): PlatformPlanningContext {
        return new PlatformPlanningContext(
            $message,
            $tenantId,
            $conversationId,
            $userId,
            $branchId,
            $language,
            $subscriptionPlan,
            $permissions,
            $grantedCapabilities,
            $grantedTools,
            $availableTools,
            $availableCapabilities,
            bin2hex(random_bytes(8)),
        );
    }
}

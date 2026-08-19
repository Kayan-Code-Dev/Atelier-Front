<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Contracts;

use DressnMore\Aos\Planner\Domain\Platform\PlatformPlanningContext;

interface PlanningContextProviderInterface
{
    /**
     * @param list<string> $permissions
     * @param list<string> $grantedCapabilities
     * @param list<string> $grantedTools
     * @param list<string> $availableTools
     * @param list<string> $availableCapabilities
     */
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
    ): PlatformPlanningContext;
}

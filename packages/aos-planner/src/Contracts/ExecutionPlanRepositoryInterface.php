<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Contracts;

use DressnMore\Aos\Planner\Domain\Platform\PlatformExecutionPlan;

interface ExecutionPlanRepositoryInterface
{
    public function save(PlatformExecutionPlan $plan): void;

    public function find(string $planId): ?PlatformExecutionPlan;

    /**
     * @return list<PlatformExecutionPlan>
     */
    public function forTenant(string $tenantId): array;
}

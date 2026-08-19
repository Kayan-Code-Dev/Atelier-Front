<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Infrastructure;

use DressnMore\Aos\Planner\Contracts\ExecutionPlanRepositoryInterface;
use DressnMore\Aos\Planner\Domain\Platform\PlatformExecutionPlan;

final class InMemoryExecutionPlanRepository implements ExecutionPlanRepositoryInterface
{
    /** @var array<string, PlatformExecutionPlan> */
    private array $byId = [];

    public function save(PlatformExecutionPlan $plan): void
    {
        $this->byId[$plan->planId()] = $plan;
    }

    public function find(string $planId): ?PlatformExecutionPlan
    {
        return $this->byId[$planId] ?? null;
    }

    public function forTenant(string $tenantId): array
    {
        $out = [];
        foreach ($this->byId as $plan) {
            if ($plan->tenantId() === $tenantId) {
                $out[] = $plan;
            }
        }

        return $out;
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Contracts;

use DressnMore\Aos\Planner\Domain\Platform\PlatformExecutionPlan;
use DressnMore\Aos\Planner\Domain\Platform\PlatformPlanningContext;

interface PlannerInterface
{
    public function plan(PlatformPlanningContext $context): PlatformExecutionPlan;
}

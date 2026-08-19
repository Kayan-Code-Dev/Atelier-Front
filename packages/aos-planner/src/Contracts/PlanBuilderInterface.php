<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Contracts;

use DressnMore\Aos\Planner\Domain\Platform\AnalyzedIntent;
use DressnMore\Aos\Planner\Domain\Platform\CapabilityMatch;
use DressnMore\Aos\Planner\Domain\Platform\PlatformExecutionPlan;
use DressnMore\Aos\Planner\Domain\Platform\PlatformPlanningContext;
use DressnMore\Aos\Planner\Domain\Platform\PolicyEvaluation;
use DressnMore\Aos\Planner\Domain\Platform\ToolSelection;

interface PlanBuilderInterface
{
    public function build(
        PlatformPlanningContext $context,
        AnalyzedIntent $intent,
        CapabilityMatch $capabilities,
        ToolSelection $tools,
        PolicyEvaluation $policy,
    ): PlatformExecutionPlan;
}

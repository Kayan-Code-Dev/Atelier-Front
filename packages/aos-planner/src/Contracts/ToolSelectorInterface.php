<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Contracts;

use DressnMore\Aos\Planner\Domain\Platform\CapabilityMatch;
use DressnMore\Aos\Planner\Domain\Platform\PlatformPlanningContext;
use DressnMore\Aos\Planner\Domain\Platform\ToolSelection;

interface ToolSelectorInterface
{
    public function select(CapabilityMatch $match, PlatformPlanningContext $context): ToolSelection;
}

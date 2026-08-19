<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Application\Platform;

use DressnMore\Aos\Planner\Contracts\ToolSelectorInterface;
use DressnMore\Aos\Planner\Domain\Platform\CapabilityMatch;
use DressnMore\Aos\Planner\Domain\Platform\PlanStep;
use DressnMore\Aos\Planner\Domain\Platform\PlatformPlanningContext;
use DressnMore\Aos\Planner\Domain\Platform\ToolSelection;

/**
 * Discovers and orders tools from the registry — never executes them.
 */
final class ToolSelector implements ToolSelectorInterface
{
    public function select(CapabilityMatch $match, PlatformPlanningContext $context): ToolSelection
    {
        return $this->selectPlan($context->availableTools(), $match, $context);
    }

    /**
     * Select by explicit ordered plan from Intent Analyzer.
     *
     * @param list<string> $toolPlan
     */
    public function selectPlan(array $toolPlan, CapabilityMatch $match, PlatformPlanningContext $context): ToolSelection
    {
        $availableSet = $context->availableTools();
        $selected = [];
        $missing = [];
        $steps = [];
        $order = 1;

        foreach ($toolPlan as $index => $tool) {
            if ($availableSet !== [] && ! in_array($tool, $availableSet, true)) {
                $missing[] = $tool;
                continue;
            }
            $selected[] = $tool;
            $steps[] = new PlanStep(
                $order,
                $tool,
                $match->required()[$index] ?? null,
                'Execute '.$tool,
            );
            $order++;
        }

        if ($selected === []) {
            $missing[] = 'empty_plan';
        }

        return new ToolSelection($selected, $steps, array_values(array_unique($missing)), []);
    }
}

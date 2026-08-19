<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Application\Platform;

use DressnMore\Aos\Planner\Contracts\PolicyEvaluatorInterface;
use DressnMore\Aos\Planner\Domain\Platform\AnalyzedIntent;
use DressnMore\Aos\Planner\Domain\Platform\PolicyEvaluation;
use DressnMore\Aos\Planner\Domain\Platform\ToolSelection;

final class PolicyEvaluator implements PolicyEvaluatorInterface
{
    /**
     * @param list<string> $policyBlockedTools tools denied by org/system policy
     */
    public function __construct(private readonly array $policyBlockedTools = []) {}

    public function evaluate(AnalyzedIntent $intent, ToolSelection $selection): PolicyEvaluation
    {
        $violations = [];
        $approvals = [];

        if (! $intent->known()) {
            $violations[] = 'intent_invalid';
        }
        if ($selection->missingTools() !== []) {
            $violations[] = 'tool_unavailable:'.implode(',', $selection->missingTools());
        }
        if ($selection->selectedTools() === []) {
            $violations[] = 'plan_without_tools';
        }

        foreach ($selection->selectedTools() as $tool) {
            if (in_array($tool, $this->policyBlockedTools, true)) {
                $violations[] = 'tool_policy_blocked:'.$tool;
            }
        }

        $tools = $selection->selectedTools();
        if (in_array('CreateReservation', $tools, true) && in_array('CancelReservation', $tools, true)) {
            $violations[] = 'conflicting_tools';
        }

        if ($intent->approval() !== null && $intent->approval() !== 'none') {
            $approvals[] = $intent->approval();
        }

        return new PolicyEvaluation(
            $violations === [],
            $approvals,
            array_values(array_unique($violations)),
            $intent->policy() ?? '',
        );
    }
}

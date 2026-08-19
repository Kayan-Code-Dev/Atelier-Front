<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Escalation;

use DressnMore\Aos\Planner\Domain\Context\PlanningContext;
use DressnMore\Aos\Planner\Domain\Intent\IntentCode;
use DressnMore\Aos\Planner\Domain\Intent\IntentResolution;
use DressnMore\Aos\Planner\Domain\Plan\PlanRiskLevel;
use DressnMore\Aos\Planner\Domain\Task\PlannedTask;

final class EscalationEvaluator
{
    /**
     * @param  list<PlannedTask>  $tasks
     */
    public function requiresEscalation(
        IntentResolution $resolution,
        array $tasks,
        PlanRiskLevel $risk,
        PlanningContext $context,
    ): bool {
        foreach ($resolution->intents() as $intent) {
            if ($intent->code()->equals(IntentCode::fromString('transfer_human'))) {
                return true;
            }
        }

        if ($risk === PlanRiskLevel::Critical) {
            return true;
        }

        if ($context->operatingMode() === 'human_only') {
            return true;
        }

        foreach ($tasks as $task) {
            if ($task->requiresApproval() && $risk->atLeast(PlanRiskLevel::High) && $context->operatingMode() === 'full_auto') {
                return true;
            }
        }

        return false;
    }
}

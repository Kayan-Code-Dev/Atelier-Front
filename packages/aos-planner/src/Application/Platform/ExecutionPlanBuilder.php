<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Application\Platform;

use DressnMore\Aos\Planner\Contracts\PlanBuilderInterface;
use DressnMore\Aos\Planner\Domain\Platform\AnalyzedIntent;
use DressnMore\Aos\Planner\Domain\Platform\CapabilityMatch;
use DressnMore\Aos\Planner\Domain\Platform\PlatformExecutionPlan;
use DressnMore\Aos\Planner\Domain\Platform\PlatformPlanningContext;
use DressnMore\Aos\Planner\Domain\Platform\PlanningStatus;
use DressnMore\Aos\Planner\Domain\Platform\PolicyEvaluation;
use DressnMore\Aos\Planner\Domain\Platform\ToolSelection;
use DateTimeImmutable;

final class ExecutionPlanBuilder implements PlanBuilderInterface
{
    public function build(
        PlatformPlanningContext $context,
        AnalyzedIntent $intent,
        CapabilityMatch $capabilities,
        ToolSelection $tools,
        PolicyEvaluation $policy,
    ): PlatformExecutionPlan {
        $status = PlanningStatus::Ready;
        $reason = '';

        if (! $intent->known()) {
            $status = PlanningStatus::Rejected;
            $reason = 'unknown_or_conflicting_intent';
        } elseif (! $capabilities->ok()) {
            $status = PlanningStatus::Rejected;
            $reason = 'missing_capabilities:'.implode(',', $capabilities->missing());
        } elseif (! $tools->ok() || ! $policy->allowed()) {
            $status = PlanningStatus::Rejected;
            $reason = $policy->violations() !== []
                ? implode('|', $policy->violations())
                : 'tool_selection_failed';
        } elseif ($policy->requiredApprovals() !== []) {
            $status = PlanningStatus::RequiresApproval;
        }

        $steps = $tools->orderedSteps();
        $complexity = match (true) {
            count($steps) >= 4 => 'high',
            count($steps) >= 2 => 'medium',
            default => 'low',
        };

        // Goal label from catalog intent name
        $goal = match ($intent->intent()) {
            'BookReservation' => 'Create Reservation',
            'CancelReservation' => 'Cancel Reservation',
            'CreateCustomer' => 'Create Customer',
            'SalesSummary' => 'Sales Summary',
            default => $intent->intent(),
        };

        return new PlatformExecutionPlan(
            'plan_'.bin2hex(random_bytes(6)),
            $context->tenantId(),
            $context->conversationId(),
            $goal,
            $intent->intent(),
            $capabilities->required(),
            $tools->selectedTools(),
            $steps,
            $policy->requiredApprovals(),
            round(count($steps) * 0.25, 2),
            $complexity,
            $status,
            (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
            $reason,
        );
    }
}

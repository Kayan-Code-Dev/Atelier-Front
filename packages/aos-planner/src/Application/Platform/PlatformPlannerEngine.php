<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Application\Platform;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Planner\Contracts\CapabilityMatcherInterface;
use DressnMore\Aos\Planner\Contracts\ExecutionPlanRepositoryInterface;
use DressnMore\Aos\Planner\Contracts\IntentAnalyzerInterface;
use DressnMore\Aos\Planner\Contracts\PlanBuilderInterface;
use DressnMore\Aos\Planner\Contracts\PlannerInterface;
use DressnMore\Aos\Planner\Contracts\PolicyEvaluatorInterface;
use DressnMore\Aos\Planner\Contracts\ToolSelectorInterface;
use DressnMore\Aos\Planner\Domain\Events\CapabilityMatched;
use DressnMore\Aos\Planner\Domain\Events\IntentResolved;
use DressnMore\Aos\Planner\Domain\Events\PlanningCompleted;
use DressnMore\Aos\Planner\Domain\Events\PlanningFailed;
use DressnMore\Aos\Planner\Domain\Events\PlanningRejected;
use DressnMore\Aos\Planner\Domain\Events\PlanningStarted;
use DressnMore\Aos\Planner\Domain\Events\ToolSelected;
use DressnMore\Aos\Planner\Domain\Platform\AnalyzedIntent;
use DressnMore\Aos\Planner\Domain\Platform\CapabilityMatch;
use DressnMore\Aos\Planner\Domain\Platform\PlatformExecutionPlan;
use DressnMore\Aos\Planner\Domain\Platform\PlatformPlanningContext;
use DressnMore\Aos\Planner\Domain\Platform\PlanningStatus;
use DressnMore\Aos\Planner\Domain\Platform\ToolSelection;
use DressnMore\Aos\Planner\Infrastructure\InMemoryExecutionPlanRepository;
use RuntimeException;
use Throwable;

/**
 * Sprint 18 Platform Planner Engine — builds Execution Plans only; never executes tools.
 */
final class PlatformPlannerEngine implements PlannerInterface
{
    public function __construct(
        private readonly IntentAnalyzerInterface $intentAnalyzer,
        private readonly CapabilityMatcherInterface $capabilityMatcher,
        private readonly ToolSelectorInterface $toolSelector,
        private readonly PolicyEvaluatorInterface $policyEvaluator,
        private readonly PermissionValidator $permissionValidator,
        private readonly SubscriptionValidator $subscriptionValidator,
        private readonly PlanBuilderInterface $planBuilder,
        private readonly ExecutionPlanRepositoryInterface $plans,
        private readonly EventBusInterface $eventBus,
    ) {}

    /**
     * @param list<string>|null $registeredCapabilities
     */
    public static function createDefault(EventBusInterface $eventBus, ?array $registeredCapabilities = null): self
    {
        return new self(
            new IntentAnalyzer(),
            new CapabilityMatcher($registeredCapabilities),
            new ToolSelector(),
            new PolicyEvaluator(),
            new PermissionValidator(),
            new SubscriptionValidator(),
            new ExecutionPlanBuilder(),
            new InMemoryExecutionPlanRepository(),
            $eventBus,
        );
    }

    public function plan(PlatformPlanningContext $context): PlatformExecutionPlan
    {
        $corr = $context->correlationId();

        try {
            $this->eventBus->publish(new PlanningStarted($corr, $context->conversationId() ?? 'none'));

            $intent = $this->intentAnalyzer->analyze($context);
            $this->eventBus->publish(new IntentResolved($corr, $intent->intent(), $intent->confidence()));

            $capabilities = $this->capabilityMatcher->match($intent);
            $this->eventBus->publish(new CapabilityMatched($corr, $capabilities->matched(), $capabilities->ok()));

            $toolPlan = $intent->known() ? $intent->toolPlan() : [];
            $tools = $this->selectTools($toolPlan, $capabilities, $context);
            $this->eventBus->publish(new ToolSelected($corr, $tools->selectedTools(), count($tools->orderedSteps())));

            if (! $intent->known()) {
                return $this->finishRejected($context, $intent, $capabilities, $tools, 'intent_unknown_or_conflicting', $corr);
            }

            if (! $capabilities->ok()) {
                return $this->finishRejected($context, $intent, $capabilities, $tools, 'capability_missing', $corr);
            }

            try {
                $this->permissionValidator->assert($context, $tools);
            } catch (RuntimeException $e) {
                return $this->finishRejected(
                    $context,
                    $intent,
                    $capabilities,
                    $tools,
                    'permission_denied',
                    $corr,
                    'permission_denied:'.$e->getMessage(),
                );
            }

            try {
                $this->subscriptionValidator->assert($context, $tools);
            } catch (RuntimeException $e) {
                return $this->finishRejected(
                    $context,
                    $intent,
                    $capabilities,
                    $tools,
                    'subscription_denied',
                    $corr,
                    'subscription_denied:'.$e->getMessage(),
                );
            }

            $policy = $this->policyEvaluator->evaluate($intent, $tools);

            if (! $policy->allowed() || ! $tools->ok()) {
                $plan = $this->planBuilder->build($context, $intent, $capabilities, $tools, $policy);
                $this->plans->save($plan);
                $this->eventBus->publish(new PlanningRejected($corr, 'policy_or_tools', $plan->rejectionReason()));
                $this->eventBus->publish(new PlanningCompleted($corr, $plan->planId(), $plan->status()->value));

                return $plan;
            }

            $plan = $this->planBuilder->build($context, $intent, $capabilities, $tools, $policy);

            if ($plan->status() === PlanningStatus::Failed) {
                $this->plans->save($plan);
                $this->eventBus->publish(new PlanningFailed($corr, 'build_failed', $plan->rejectionReason()));
                $this->eventBus->publish(new PlanningCompleted($corr, $plan->planId(), $plan->status()->value));

                return $plan;
            }

            $this->plans->save($plan);
            $this->eventBus->publish(new PlanningCompleted($corr, $plan->planId(), $plan->status()->value));

            return $plan;
        } catch (Throwable $e) {
            $this->eventBus->publish(new PlanningFailed($corr, 'engine_exception', $e->getMessage()));
            throw $e;
        }
    }

    /**
     * @param list<string> $toolPlan
     */
    private function selectTools(array $toolPlan, CapabilityMatch $capabilities, PlatformPlanningContext $context): ToolSelection
    {
        if ($this->toolSelector instanceof ToolSelector) {
            return $this->toolSelector->selectPlan($toolPlan, $capabilities, $context);
        }

        return $this->toolSelector->select($capabilities, $context);
    }

    private function finishRejected(
        PlatformPlanningContext $context,
        AnalyzedIntent $intent,
        CapabilityMatch $capabilities,
        ToolSelection $tools,
        string $reasonCode,
        string $corr,
        ?string $forcedReason = null,
    ): PlatformExecutionPlan {
        $policy = $this->policyEvaluator->evaluate($intent, $tools);
        $plan = $this->planBuilder->build($context, $intent, $capabilities, $tools, $policy);

        if ($forcedReason !== null || $plan->status() !== PlanningStatus::Rejected) {
            $plan = new PlatformExecutionPlan(
                $plan->planId(),
                $plan->tenantId(),
                $plan->conversationId(),
                $plan->goal(),
                $plan->intent(),
                $plan->requiredCapabilities(),
                $plan->selectedTools(),
                $plan->orderedSteps(),
                $plan->requiredApprovals(),
                $plan->estimatedCost(),
                $plan->estimatedComplexity(),
                PlanningStatus::Rejected,
                $plan->createdAt(),
                $forcedReason ?? ($plan->rejectionReason() !== '' ? $plan->rejectionReason() : $reasonCode),
            );
        }

        $this->plans->save($plan);
        $this->eventBus->publish(new PlanningRejected($corr, $reasonCode, $plan->rejectionReason()));
        $this->eventBus->publish(new PlanningCompleted($corr, $plan->planId(), $plan->status()->value));

        return $plan;
    }
}

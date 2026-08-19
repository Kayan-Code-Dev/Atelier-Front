<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Application;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Planner\Application\Platform\PlatformPlannerEngine;
use DressnMore\Aos\Planner\Contracts\PlannerInterface;
use DressnMore\Aos\Planner\Domain\Platform\PlatformPlanningContext;
use DressnMore\Aos\Planner\Domain\Platform\PlanningStatus;
use DressnMore\Aos\Response\Contracts\ResponseEngineInterface;
use DressnMore\Aos\Response\Contracts\ResultAggregatorInterface;
use DressnMore\Aos\Response\Domain\Response\EndToEndResult;
use DressnMore\Aos\Response\Domain\Response\FinalAiResponse;
use DressnMore\Aos\Response\Domain\Response\ResponseContext;
use DressnMore\Aos\Response\Domain\Response\ResponseStatus;
use DressnMore\Aos\Response\Domain\Policy\ResponsePolicy;

/**
 * User → Planner → Gateway(steps) → Response — first AI Core Platform cycle.
 */
final class EndToEndAiOrchestrator
{
    public function __construct(
        private readonly PlannerInterface $planner,
        private readonly PlanStepExecutor $executor,
        private readonly ResultAggregatorInterface $aggregator,
        private readonly ResponseEngineInterface $responseEngine,
        private readonly LocalizationService $i18n,
    ) {}

    public static function createDefault(EventBusInterface $eventBus, ?PlanStepExecutor $executor = null): self
    {
        $i18n = new LocalizationService('ar');
        $policy = new ResponsePolicy();
        $formatter = new ResultFormatter($i18n, $policy);
        $errors = new ErrorResponseGenerator($i18n, $policy);
        $builder = new ResponseBuilder($i18n, $formatter, $errors, $policy);
        $engine = new ResponseEngine($builder, $eventBus);

        return new self(
            PlatformPlannerEngine::createDefault($eventBus),
            $executor ?? new PlanStepExecutor(),
            new ResultAggregator(),
            $engine,
            $i18n,
        );
    }

    /**
     * @param array<string, mixed> $toolInputs
     * @param list<string> $permissions
     */
    public function handle(
        string $message,
        string $tenantId,
        string $locale = 'ar',
        ?string $conversationId = null,
        string $subscriptionPlan = 'professional',
        array $toolInputs = [],
        array $permissions = ['*'],
    ): EndToEndResult {
        $planningContext = new PlatformPlanningContext(
            $message,
            $tenantId,
            $conversationId,
            null,
            null,
            $locale,
            $subscriptionPlan,
            [],
            [],
            [],
            [],
            [],
            bin2hex(random_bytes(6)),
        );

        $plan = $this->planner->plan($planningContext);

        if (! $plan->isReadyForGateway()) {
            $i18n = $this->i18n->withLocale($locale);
            $reason = $plan->rejectionReason() !== '' ? $plan->rejectionReason() : $plan->status()->value;
            $response = new FinalAiResponse(
                $i18n->translate('generic_failure', [], $locale),
                ResponseStatus::Failed,
                $locale,
                [$reason],
                ['planning_status' => $plan->status()->value, 'reason' => $reason],
                [],
                $plan->planId(),
                $planningContext->correlationId(),
            );
            $empty = $this->aggregator->aggregate([]);

            return new EndToEndResult($plan, $empty, $response);
        }

        // Requires approval still produces a plan for gateway preview; execute for Core cycle demo.
        if ($plan->status() === PlanningStatus::RequiresApproval && $plan->orderedSteps() === []) {
            $empty = $this->aggregator->aggregate([]);
            $response = $this->responseEngine->generate(
                new ResponseContext($locale, $plan->intent(), $plan->goal(), $tenantId, $conversationId, $plan->planId(), $planningContext->correlationId()),
                $empty,
            );

            return new EndToEndResult($plan, $empty, $response);
        }

        $outcomes = $this->executor->execute($plan, $toolInputs, $permissions, $plan->requiredCapabilities());
        $aggregated = $this->aggregator->aggregate($outcomes);
        $responseContext = new ResponseContext(
            $locale,
            $plan->intent(),
            $plan->goal(),
            $tenantId,
            $conversationId,
            $plan->planId(),
            $planningContext->correlationId(),
        );
        $response = $this->responseEngine->generate($responseContext, $aggregated);

        return new EndToEndResult($plan, $aggregated, $response);
    }
}

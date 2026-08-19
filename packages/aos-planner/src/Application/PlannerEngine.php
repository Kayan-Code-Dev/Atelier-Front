<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Application;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Planner\Domain\Clarification\ClarificationEngine;
use DressnMore\Aos\Planner\Domain\Confidence\ConfidenceEvaluator;
use DressnMore\Aos\Planner\Domain\Context\PlanningContext;
use DressnMore\Aos\Planner\Domain\Escalation\EscalationEvaluator;
use DressnMore\Aos\Planner\Domain\Events\ClarificationRequired;
use DressnMore\Aos\Planner\Domain\Events\EscalationRequired;
use DressnMore\Aos\Planner\Domain\Events\GoalsResolved;
use DressnMore\Aos\Planner\Domain\Events\IntentResolved;
use DressnMore\Aos\Planner\Domain\Events\PlanGenerated;
use DressnMore\Aos\Planner\Domain\Events\PlanValidated;
use DressnMore\Aos\Planner\Domain\Events\PlanningCompleted;
use DressnMore\Aos\Planner\Domain\Events\PlanningStarted;
use DressnMore\Aos\Planner\Domain\Goal\GoalResolver;
use DressnMore\Aos\Planner\Domain\Intent\IntentCatalog;
use DressnMore\Aos\Planner\Domain\Intent\IntentResolver;
use DressnMore\Aos\Planner\Domain\Plan\ExecutionPlan;
use DressnMore\Aos\Planner\Domain\Plan\ExecutionPlanner;
use DressnMore\Aos\Planner\Domain\Plan\PlanningDecision;
use DressnMore\Aos\Planner\Domain\Plan\PlanningValidator;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningBag;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningPipeline;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningStage;
use DressnMore\Aos\Planner\Domain\Pipeline\Stages\GoalResolutionStage;
use DressnMore\Aos\Planner\Domain\Pipeline\Stages\IntentResolutionStage;
use DressnMore\Aos\Planner\Domain\Pipeline\Stages\PlanGenerationAndDecisionStage;
use DressnMore\Aos\Planner\Domain\Pipeline\Stages\TaskDecompositionStage;
use DressnMore\Aos\Planner\Domain\Policy\PlanningPolicy;
use DressnMore\Aos\Planner\Domain\Session\PlanningSession;
use DressnMore\Aos\Planner\Domain\Strategy\PlanningStrategy;
use DressnMore\Aos\Planner\Domain\Task\TaskPlanner;

/**
 * Planner Engine — produces Execution Plans only; never calls LLM or executes tools.
 */
final class PlannerEngine
{
    public function __construct(
        private readonly PlanningPipeline $pipeline,
        private readonly EventBusInterface $eventBus,
    ) {}

    public static function createDefault(EventBusInterface $eventBus): self
    {
        $catalog = new IntentCatalog();
        $intentResolver = new IntentResolver($catalog);
        $goalResolver = new GoalResolver($catalog);
        $taskPlanner = new TaskPlanner($catalog);
        $policy = new PlanningPolicy();
        $pipeline = new PlanningPipeline([
            new IntentResolutionStage($intentResolver),
            new GoalResolutionStage($goalResolver),
            new TaskDecompositionStage($taskPlanner),
            new PlanGenerationAndDecisionStage(
                new ExecutionPlanner(),
                new PlanningValidator($policy),
                $policy,
                new ClarificationEngine(),
                new EscalationEvaluator(),
                new ConfidenceEvaluator(),
                new PlanningStrategy(),
            ),
        ]);

        return new self($pipeline, $eventBus);
    }

    public function plan(PlanningContext $context): ExecutionPlan
    {
        $session = PlanningSession::start($context);
        $bag = new PlanningBag($session);
        $bag->mark(PlanningStage::IncomingRequest->value);

        $this->eventBus->publish(new PlanningStarted(
            $context->correlationId(),
            $session->id()->toString(),
        ));

        $this->pipeline->process($bag);

        $resolution = $bag->intentResolution();
        if ($resolution !== null) {
            $this->eventBus->publish(new IntentResolved(
                $context->correlationId(),
                $resolution->kind()->value,
                $resolution->overallConfidence(),
            ));
        }

        $goalCodes = array_map(
            static fn ($g) => $g->code()->toString(),
            $bag->goals()
        );
        $this->eventBus->publish(new GoalsResolved($context->correlationId(), $goalCodes));

        $plan = $bag->plan();
        if ($plan === null) {
            throw new \RuntimeException('Planner pipeline did not produce an ExecutionPlan.');
        }

        $this->eventBus->publish(new PlanGenerated(
            $context->correlationId(),
            $plan->id()->toString(),
            count($plan->tasks()),
        ));

        $this->eventBus->publish(new PlanValidated(
            $context->correlationId(),
            $plan->id()->toString(),
            $plan->validationNotes() === '' || ! str_contains($plan->validationNotes(), 'exceeds'),
            $plan->validationNotes(),
        ));

        if ($plan->decision() === PlanningDecision::ClarificationRequired) {
            $this->eventBus->publish(new ClarificationRequired(
                $context->correlationId(),
                $plan->clarificationPrompt() ?? '',
            ));
        }

        if ($plan->decision() === PlanningDecision::EscalationRequired) {
            $this->eventBus->publish(new EscalationRequired(
                $context->correlationId(),
                $plan->expectedOutcome(),
            ));
        }

        $this->eventBus->publish(new PlanningCompleted(
            $context->correlationId(),
            $plan->id()->toString(),
            $plan->decision()->value,
        ));

        return $plan;
    }
}

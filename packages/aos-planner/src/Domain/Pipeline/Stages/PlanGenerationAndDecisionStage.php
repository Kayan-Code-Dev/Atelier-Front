<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Pipeline\Stages;

use DressnMore\Aos\Planner\Domain\Clarification\ClarificationEngine;
use DressnMore\Aos\Planner\Domain\Confidence\ConfidenceEvaluator;
use DressnMore\Aos\Planner\Domain\Escalation\EscalationEvaluator;
use DressnMore\Aos\Planner\Domain\Plan\ExecutionPlanner;
use DressnMore\Aos\Planner\Domain\Plan\PlanningDecision;
use DressnMore\Aos\Planner\Domain\Plan\PlanningValidator;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningBag;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningStage;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningStageInterface;
use DressnMore\Aos\Planner\Domain\Policy\PlanningPolicy;
use DressnMore\Aos\Planner\Domain\Strategy\PlanningStrategy;

final class PlanGenerationAndDecisionStage implements PlanningStageInterface
{
    public function __construct(
        private readonly ExecutionPlanner $executionPlanner,
        private readonly PlanningValidator $validator,
        private readonly PlanningPolicy $policy,
        private readonly ClarificationEngine $clarification,
        private readonly EscalationEvaluator $escalation,
        private readonly ConfidenceEvaluator $confidence,
        private readonly PlanningStrategy $strategy,
    ) {}

    public function name(): PlanningStage
    {
        return PlanningStage::ExecutionPlanGeneration;
    }

    public function process(PlanningBag $bag): void
    {
        $resolution = $bag->intentResolution();
        if ($resolution === null) {
            return;
        }

        $confidence = $this->confidence->evaluate($resolution, $bag->tasks());
        $bag->setConfidence($confidence);

        $notes = $this->validator->validate($bag->tasks(), $bag->graph());
        $bag->setValidationNotes(implode('; ', $notes));
        $bag->mark(PlanningStage::Validation->value);

        $risk = $this->policy->deriveRisk($bag->tasks());
        $approval = $this->policy->requiresApproval($bag->tasks());
        $escalate = $this->escalation->requiresEscalation(
            $resolution,
            $bag->tasks(),
            $risk,
            $bag->context(),
        );

        $decision = PlanningDecision::ReadyToExecute;
        $clarification = null;
        $expected = 'Execute planned tool candidates via Tool Gateway (downstream).';

        if ($this->clarification->requiresClarification($resolution)) {
            $decision = PlanningDecision::ClarificationRequired;
            $clarification = $this->clarification->promptFor($resolution);
            $bag->setClarificationPrompt($clarification);
            $expected = 'Ask customer for clarification before any tool execution.';
        } elseif ($escalate) {
            $decision = PlanningDecision::EscalationRequired;
            $expected = 'Escalate to human; do not auto-execute writes.';
        } elseif ($notes !== []) {
            $decision = PlanningDecision::Rejected;
            $expected = 'Plan rejected by validator: '.implode('; ', $notes);
        }

        $strategy = $this->strategy->forIntentKind($resolution->kind());
        $bag->setValidationNotes(trim($bag->validationNotes().'; strategy='.$strategy));

        $plan = $this->executionPlanner->generate(
            $resolution,
            $bag->goals(),
            $bag->tasks(),
            $confidence,
            $decision,
            $risk,
            $approval,
            $escalate || $decision === PlanningDecision::EscalationRequired,
            $expected,
            $clarification,
            $bag->validationNotes(),
        );

        $bag->setPlan($plan);
        $bag->setDecision($decision);
        $bag->mark(PlanningStage::Decision->value);
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Application;

use DressnMore\Aos\Planner\Domain\Clarification\ClarificationEngine;
use DressnMore\Aos\Planner\Domain\Confidence\ConfidenceEvaluator;
use DressnMore\Aos\Planner\Domain\Escalation\EscalationEvaluator;
use DressnMore\Aos\Planner\Domain\Goal\GoalResolver;
use DressnMore\Aos\Planner\Domain\Intent\IntentCatalog;
use DressnMore\Aos\Planner\Domain\Intent\IntentResolver;
use DressnMore\Aos\Planner\Domain\Plan\ExecutionPlanner;
use DressnMore\Aos\Planner\Domain\Plan\PlanningValidator;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningPipeline;
use DressnMore\Aos\Planner\Domain\Pipeline\Stages\GoalResolutionStage;
use DressnMore\Aos\Planner\Domain\Pipeline\Stages\IntentResolutionStage;
use DressnMore\Aos\Planner\Domain\Pipeline\Stages\PlanGenerationAndDecisionStage;
use DressnMore\Aos\Planner\Domain\Pipeline\Stages\TaskDecompositionStage;
use DressnMore\Aos\Planner\Domain\Policy\PlanningPolicy;
use DressnMore\Aos\Planner\Domain\Strategy\PlanningStrategy;
use DressnMore\Aos\Planner\Domain\Task\TaskPlanner;

final class PlanningPipelineFactory
{
    public function __construct(
        private readonly IntentCatalog $catalog = new IntentCatalog(),
        private readonly PlanningPolicy $policy = new PlanningPolicy(),
    ) {}

    public function create(): PlanningPipeline
    {
        return new PlanningPipeline([
            new IntentResolutionStage(new IntentResolver($this->catalog)),
            new GoalResolutionStage(new GoalResolver($this->catalog)),
            new TaskDecompositionStage(new TaskPlanner($this->catalog)),
            new PlanGenerationAndDecisionStage(
                new ExecutionPlanner(),
                new PlanningValidator($this->policy),
                $this->policy,
                new ClarificationEngine(),
                new EscalationEvaluator(),
                new ConfidenceEvaluator(),
                new PlanningStrategy(),
            ),
        ]);
    }
}

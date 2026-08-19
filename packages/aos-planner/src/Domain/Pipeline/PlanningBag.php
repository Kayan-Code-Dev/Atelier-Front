<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Pipeline;

use DressnMore\Aos\Planner\Domain\Context\PlanningContext;
use DressnMore\Aos\Planner\Domain\Goal\PlanningGoal;
use DressnMore\Aos\Planner\Domain\Intent\IntentResolution;
use DressnMore\Aos\Planner\Domain\Plan\ExecutionGraph;
use DressnMore\Aos\Planner\Domain\Plan\ExecutionPlan;
use DressnMore\Aos\Planner\Domain\Plan\PlanningDecision;
use DressnMore\Aos\Planner\Domain\Session\PlanningSession;
use DressnMore\Aos\Planner\Domain\Task\PlannedTask;

/**
 * Mutable bag for the Planning Pipeline.
 */
final class PlanningBag
{
    private ?IntentResolution $intentResolution = null;

    /** @var list<PlanningGoal> */
    private array $goals = [];

    /** @var list<PlannedTask> */
    private array $tasks = [];

    private ?ExecutionGraph $graph = null;

    private ?ExecutionPlan $plan = null;

    private ?PlanningDecision $decision = null;

    private string $clarificationPrompt = '';

    private string $validationNotes = '';

    private float $confidence = 0.0;

    /** @var list<string> */
    private array $stages = [];

    public function __construct(
        private readonly PlanningSession $session,
    ) {}

    public function session(): PlanningSession
    {
        return $this->session;
    }

    public function context(): PlanningContext
    {
        return $this->session->context();
    }

    public function mark(string $stage): void
    {
        $this->stages[] = $stage;
    }

    /**
     * @return list<string>
     */
    public function stages(): array
    {
        return $this->stages;
    }

    public function setIntentResolution(IntentResolution $resolution): void
    {
        $this->intentResolution = $resolution;
        $this->session->setIntentResolution($resolution);
        $this->confidence = $resolution->overallConfidence();
    }

    public function intentResolution(): ?IntentResolution
    {
        return $this->intentResolution;
    }

    /**
     * @param  list<PlanningGoal>  $goals
     */
    public function setGoals(array $goals): void
    {
        $this->goals = $goals;
    }

    /**
     * @return list<PlanningGoal>
     */
    public function goals(): array
    {
        return $this->goals;
    }

    /**
     * @param  list<PlannedTask>  $tasks
     */
    public function setTasks(array $tasks): void
    {
        $this->tasks = $tasks;
        $this->graph = ExecutionGraph::fromTasks($tasks);
    }

    /**
     * @return list<PlannedTask>
     */
    public function tasks(): array
    {
        return $this->tasks;
    }

    public function graph(): ?ExecutionGraph
    {
        return $this->graph;
    }

    public function setPlan(ExecutionPlan $plan): void
    {
        $this->plan = $plan;
        $this->session->setPlan($plan);
    }

    public function plan(): ?ExecutionPlan
    {
        return $this->plan;
    }

    public function setDecision(PlanningDecision $decision): void
    {
        $this->decision = $decision;
    }

    public function decision(): ?PlanningDecision
    {
        return $this->decision;
    }

    public function setClarificationPrompt(string $prompt): void
    {
        $this->clarificationPrompt = $prompt;
    }

    public function clarificationPrompt(): string
    {
        return $this->clarificationPrompt;
    }

    public function setValidationNotes(string $notes): void
    {
        $this->validationNotes = $notes;
    }

    public function validationNotes(): string
    {
        return $this->validationNotes;
    }

    public function setConfidence(float $confidence): void
    {
        $this->confidence = $confidence;
    }

    public function confidence(): float
    {
        return $this->confidence;
    }
}

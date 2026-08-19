<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Plan;

use DressnMore\Aos\Planner\Domain\Goal\PlanningGoal;
use DressnMore\Aos\Planner\Domain\Intent\IntentKind;
use DressnMore\Aos\Planner\Domain\Task\PlannedTask;

/**
 * Immutable Execution Plan — planning output only; never executes tools.
 */
final class ExecutionPlan
{
    /**
     * @param  list<PlanningGoal>  $goals
     * @param  list<PlannedTask>  $tasks
     * @param  list<string>  $toolCandidates
     * @param  array<string, list<string>>  $dependencies  taskId => dependsOn[]
     */
    public function __construct(
        private readonly ExecutionPlanId $id,
        private readonly IntentKind $intentKind,
        private readonly array $goals,
        private readonly array $tasks,
        private readonly array $toolCandidates,
        private readonly array $dependencies,
        private readonly PlanRiskLevel $risk,
        private readonly float $confidence,
        private readonly bool $approvalRequired,
        private readonly bool $humanEscalation,
        private readonly PlanningDecision $decision,
        private readonly string $expectedOutcome,
        private readonly ?string $clarificationPrompt = null,
        private readonly string $validationNotes = '',
    ) {}

    public function id(): ExecutionPlanId
    {
        return $this->id;
    }

    public function intentKind(): IntentKind
    {
        return $this->intentKind;
    }

    /**
     * @return list<PlanningGoal>
     */
    public function goals(): array
    {
        return $this->goals;
    }

    /**
     * @return list<PlannedTask>
     */
    public function tasks(): array
    {
        return $this->tasks;
    }

    /**
     * @return list<string>
     */
    public function toolCandidates(): array
    {
        return $this->toolCandidates;
    }

    /**
     * @return array<string, list<string>>
     */
    public function dependencies(): array
    {
        return $this->dependencies;
    }

    public function risk(): PlanRiskLevel
    {
        return $this->risk;
    }

    public function confidence(): float
    {
        return $this->confidence;
    }

    public function approvalRequired(): bool
    {
        return $this->approvalRequired;
    }

    public function humanEscalation(): bool
    {
        return $this->humanEscalation;
    }

    public function decision(): PlanningDecision
    {
        return $this->decision;
    }

    public function expectedOutcome(): string
    {
        return $this->expectedOutcome;
    }

    public function clarificationPrompt(): ?string
    {
        return $this->clarificationPrompt;
    }

    public function validationNotes(): string
    {
        return $this->validationNotes;
    }

    public function isExecutable(): bool
    {
        return $this->decision === PlanningDecision::ReadyToExecute;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'intent_kind' => $this->intentKind->value,
            'goals' => array_map(static fn (PlanningGoal $g): string => $g->code()->toString(), $this->goals),
            'tasks' => array_map(static fn (PlannedTask $t): string => $t->id()->toString(), $this->tasks),
            'tool_candidates' => $this->toolCandidates,
            'dependencies' => $this->dependencies,
            'risk' => $this->risk->value,
            'confidence' => $this->confidence,
            'approval_required' => $this->approvalRequired,
            'human_escalation' => $this->humanEscalation,
            'decision' => $this->decision->value,
            'expected_outcome' => $this->expectedOutcome,
            'clarification_prompt' => $this->clarificationPrompt,
        ];
    }
}

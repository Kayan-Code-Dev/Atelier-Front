<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Plan;

use DressnMore\Aos\Planner\Domain\Goal\PlanningGoal;
use DressnMore\Aos\Planner\Domain\Intent\IntentKind;
use DressnMore\Aos\Planner\Domain\Intent\IntentResolution;
use DressnMore\Aos\Planner\Domain\Task\PlannedTask;

/**
 * Builds an immutable ExecutionPlan from goals/tasks/graph.
 */
final class ExecutionPlanner
{
    /**
     * @param  list<PlanningGoal>  $goals
     * @param  list<PlannedTask>  $tasks
     */
    public function generate(
        IntentResolution $resolution,
        array $goals,
        array $tasks,
        float $confidence,
        PlanningDecision $decision,
        PlanRiskLevel $risk,
        bool $approvalRequired,
        bool $humanEscalation,
        string $expectedOutcome,
        ?string $clarificationPrompt = null,
        string $validationNotes = '',
    ): ExecutionPlan {
        $graph = ExecutionGraph::fromTasks($tasks);
        $ordered = $graph->topologicalOrder() ?? $tasks;

        // Enforce reads-before-writes while preserving relative order within each group.
        $reads = [];
        $writes = [];
        foreach ($ordered as $task) {
            if ($task->isWrite()) {
                $writes[] = $task;
            } else {
                $reads[] = $task;
            }
        }
        $ordered = array_merge($reads, $writes);

        $toolCandidates = [];
        $dependencies = [];
        foreach ($ordered as $task) {
            foreach ($task->toolCandidates() as $tool) {
                $toolCandidates[] = $tool;
            }
            $dependencies[$task->id()->toString()] = $task->dependsOnTaskIds();
        }

        return new ExecutionPlan(
            ExecutionPlanId::generate(),
            $resolution->kind(),
            $goals,
            $ordered,
            array_values(array_unique($toolCandidates)),
            $dependencies,
            $risk,
            $confidence,
            $approvalRequired,
            $humanEscalation,
            $decision,
            $expectedOutcome,
            $clarificationPrompt,
            $validationNotes,
        );
    }
}

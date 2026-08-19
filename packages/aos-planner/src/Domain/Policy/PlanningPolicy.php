<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Policy;

use DressnMore\Aos\Planner\Domain\Plan\PlanRiskLevel;
use DressnMore\Aos\Planner\Domain\Task\PlannedTask;

/**
 * Planning policies (reads before writes, max steps, approval gates).
 */
final class PlanningPolicy
{
    public function __construct(
        private readonly int $maxSteps = 8,
        private readonly bool $readsBeforeWrites = true,
    ) {}

    public function maxSteps(): int
    {
        return $this->maxSteps;
    }

    /**
     * @param  list<PlannedTask>  $tasks
     * @return list<string>
     */
    public function validateTasks(array $tasks): array
    {
        $notes = [];
        if (count($tasks) > $this->maxSteps) {
            $notes[] = sprintf('task count %d exceeds maxSteps %d', count($tasks), $this->maxSteps);
        }

        if ($this->readsBeforeWrites) {
            $sawWrite = false;
            foreach ($tasks as $task) {
                if ($task->isWrite()) {
                    $sawWrite = true;
                } elseif ($sawWrite) {
                    $notes[] = 'read task appears after write task (violates reads-before-writes)';
                    break;
                }
            }
        }

        return $notes;
    }

    /**
     * @param  list<PlannedTask>  $tasks
     */
    public function deriveRisk(array $tasks): PlanRiskLevel
    {
        $level = PlanRiskLevel::Low;
        foreach ($tasks as $task) {
            if ($task->requiresApproval()) {
                $level = PlanRiskLevel::High;
            } elseif ($task->isWrite() && $level === PlanRiskLevel::Low) {
                $level = PlanRiskLevel::Medium;
            }
        }

        return $level;
    }

    /**
     * @param  list<PlannedTask>  $tasks
     */
    public function requiresApproval(array $tasks): bool
    {
        foreach ($tasks as $task) {
            if ($task->requiresApproval()) {
                return true;
            }
        }

        return false;
    }
}

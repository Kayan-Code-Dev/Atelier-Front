<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Plan;

use DressnMore\Aos\Planner\Domain\Policy\PlanningPolicy;
use DressnMore\Aos\Planner\Domain\Task\PlannedTask;

final class PlanningValidator
{
    public function __construct(
        private readonly PlanningPolicy $policy = new PlanningPolicy(),
    ) {}

    /**
     * @param  list<PlannedTask>  $tasks
     * @return list<string>
     */
    public function validate(array $tasks, ?ExecutionGraph $graph): array
    {
        $notes = $this->policy->validateTasks($tasks);
        if ($graph !== null && $graph->hasCycle()) {
            $notes[] = 'execution graph contains a cycle';
        }

        return $notes;
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Infrastructure\InMemory;

use DressnMore\Aos\Workflow\Domain\Execution\TaskDispatcher;
use DressnMore\Aos\Workflow\Domain\Task\TaskDefinition;
use DressnMore\Aos\Workflow\Domain\Task\TaskType;

final class StubTaskDispatcher implements TaskDispatcher
{
    public function dispatch(TaskDefinition $task, array $runtime): bool
    {
        if ($task->type() === TaskType::ParallelTask) {
            return true;
        }

        if ($task->type() === TaskType::ConditionTask) {
            return (bool) ($task->config()['result'] ?? true);
        }

        return true;
    }
}

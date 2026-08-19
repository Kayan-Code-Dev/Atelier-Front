<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Execution;

use DressnMore\Aos\Workflow\Domain\Task\TaskDefinition;

interface TaskDispatcher
{
    /**
     * @param array<string,mixed> $runtime
     */
    public function dispatch(TaskDefinition $task, array $runtime): bool;
}

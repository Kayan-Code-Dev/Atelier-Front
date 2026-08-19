<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Contracts;

use DressnMore\Aos\Workflow\Domain\Execution\WorkflowExecutionResult;

interface WorkflowEngineInterface
{
    /**
     * @param array<string,mixed> $triggerPayload
     */
    public function run(array $triggerPayload): WorkflowExecutionResult;
}

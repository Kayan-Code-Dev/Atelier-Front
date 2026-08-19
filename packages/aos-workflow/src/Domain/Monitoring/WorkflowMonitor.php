<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Monitoring;

use DressnMore\Aos\Workflow\Domain\Execution\WorkflowExecutionResult;

final class WorkflowMonitor
{
    /** @var list<WorkflowExecutionResult> */
    private array $history = [];

    public function record(WorkflowExecutionResult $result): void
    {
        $this->history[] = $result;
    }

    /**
     * @return list<WorkflowExecutionResult>
     */
    public function history(): array
    {
        return $this->history;
    }
}

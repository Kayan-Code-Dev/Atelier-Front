<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Execution;

use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowId;

final class WorkflowExecutionResult
{
    /**
     * @param list<string> $completedTasks
     * @param list<string> $failedTasks
     * @param list<string> $trace
     */
    public function __construct(
        private readonly WorkflowId $workflowId,
        private readonly bool $success,
        private readonly array $completedTasks = [],
        private readonly array $failedTasks = [],
        private readonly array $trace = [],
    ) {}

    public function workflowId(): WorkflowId { return $this->workflowId; }
    public function success(): bool { return $this->success; }
    /** @return list<string> */
    public function completedTasks(): array { return $this->completedTasks; }
    /** @return list<string> */
    public function failedTasks(): array { return $this->failedTasks; }
    /** @return list<string> */
    public function trace(): array { return $this->trace; }
}

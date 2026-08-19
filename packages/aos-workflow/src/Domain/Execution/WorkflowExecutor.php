<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Execution;

use DressnMore\Aos\Workflow\Domain\Retry\RetryManager;
use DressnMore\Aos\Workflow\Domain\Retry\RetryPolicyType;
use DressnMore\Aos\Workflow\Domain\Task\TaskType;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowDefinition;

final class WorkflowExecutor
{
    public function __construct(
        private readonly TaskDispatcher $dispatcher,
        private readonly RetryManager $retryManager,
    ) {}

    /**
     * @param array<string,mixed> $runtime
     */
    public function execute(WorkflowDefinition $workflow, array $runtime = []): WorkflowExecutionResult
    {
        $completed = [];
        $failed = [];
        $trace = ['trigger', 'load_workflow', 'validate', 'build_context', 'evaluate_conditions', 'execute_tasks'];

        foreach ($workflow->tasks() as $task) {
            if ($task->type() === TaskType::ParallelTask) {
                $trace[] = 'parallel:'.$task->id();
            }

            $ok = $this->dispatcher->dispatch($task, $runtime);
            if ($ok) {
                $completed[] = $task->id();
                continue;
            }

            $failed[] = $task->id();
            $trace[] = 'handle_errors';
            $delay = $this->retryManager->nextDelaySeconds(RetryPolicyType::Immediate, 1);
            $trace[] = 'retry:'.$delay;
            break;
        }

        if ($failed === []) {
            $trace[] = 'complete';
        }

        return new WorkflowExecutionResult($workflow->id(), $failed === [], $completed, $failed, $trace);
    }
}

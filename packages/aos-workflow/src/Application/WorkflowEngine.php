<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Application;

use DressnMore\Aos\Workflow\Contracts\WorkflowEngineInterface;
use DressnMore\Aos\Workflow\Domain\Execution\WorkflowExecutionResult;
use DressnMore\Aos\Workflow\Domain\Execution\WorkflowExecutor;
use DressnMore\Aos\Workflow\Domain\Monitoring\WorkflowMetrics;
use DressnMore\Aos\Workflow\Domain\Monitoring\WorkflowMonitor;
use DressnMore\Aos\Workflow\Domain\Trigger\TriggerEngine;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowId;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowRegistry;

final class WorkflowEngine implements WorkflowEngineInterface
{
    public function __construct(
        private readonly TriggerEngine $triggerEngine,
        private readonly WorkflowRegistry $registry,
        private readonly WorkflowExecutor $executor,
        private readonly WorkflowMonitor $monitor,
        private readonly WorkflowMetrics $metrics,
    ) {}

    public function run(array $triggerPayload): WorkflowExecutionResult
    {
        $this->metrics->incStarted();
        $trigger = $this->triggerEngine->resolve($triggerPayload);
        $workflow = $this->registry->forTrigger($trigger);

        if ($workflow === null) {
            $this->metrics->incFailed();
            return new WorkflowExecutionResult(WorkflowId::fromString('wf_missing'), false, [], ['workflow_not_found'], ['trigger', 'load_workflow']);
        }

        $result = $this->executor->execute($workflow, $triggerPayload);
        $this->monitor->record($result);
        if ($result->success()) {
            $this->metrics->incCompleted();
        } else {
            $this->metrics->incFailed();
        }

        return $result;
    }
}

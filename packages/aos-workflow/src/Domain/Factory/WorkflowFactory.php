<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Factory;

use DressnMore\Aos\Workflow\Domain\Task\TaskDefinition;
use DressnMore\Aos\Workflow\Domain\Trigger\TriggerType;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowDefinition;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowId;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowLifecycleStatus;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowType;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowVersion;

final class WorkflowFactory
{
    /**
     * @param list<TaskDefinition> $tasks
     */
    public function create(
        string $name,
        WorkflowType $type,
        TriggerType $trigger,
        array $tasks,
        ?WorkflowId $id = null,
    ): WorkflowDefinition {
        return new WorkflowDefinition(
            $id ?? WorkflowId::generate(),
            $name,
            $type,
            $trigger,
            WorkflowVersion::initial(),
            WorkflowLifecycleStatus::Published,
            $tasks,
        );
    }
}

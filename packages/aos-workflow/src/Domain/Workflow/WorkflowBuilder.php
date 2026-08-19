<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Workflow;

use DressnMore\Aos\Workflow\Domain\Factory\WorkflowFactory;
use DressnMore\Aos\Workflow\Domain\Task\TaskDefinition;
use DressnMore\Aos\Workflow\Domain\Trigger\TriggerType;

final class WorkflowBuilder
{
    /** @var list<TaskDefinition> */
    private array $tasks = [];

    public function __construct(private readonly WorkflowFactory $factory) {}

    public function addTask(TaskDefinition $task): self
    {
        $this->tasks[] = $task;
        return $this;
    }

    public function build(string $name, WorkflowType $type, TriggerType $trigger): WorkflowDefinition
    {
        return $this->factory->create($name, $type, $trigger, $this->tasks);
    }
}

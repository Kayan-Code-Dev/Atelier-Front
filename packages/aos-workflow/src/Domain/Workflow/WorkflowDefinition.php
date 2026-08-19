<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Workflow;

use DressnMore\Aos\Workflow\Domain\Task\TaskDefinition;
use DressnMore\Aos\Workflow\Domain\Trigger\TriggerType;

final class WorkflowDefinition
{
    /**
     * @param list<TaskDefinition> $tasks
     */
    public function __construct(
        private readonly WorkflowId $id,
        private readonly string $name,
        private readonly WorkflowType $type,
        private readonly TriggerType $trigger,
        private readonly WorkflowVersion $version,
        private readonly WorkflowLifecycleStatus $status,
        private readonly array $tasks,
    ) {}

    public function id(): WorkflowId { return $this->id; }
    public function name(): string { return $this->name; }
    public function type(): WorkflowType { return $this->type; }
    public function trigger(): TriggerType { return $this->trigger; }
    public function version(): WorkflowVersion { return $this->version; }
    public function status(): WorkflowLifecycleStatus { return $this->status; }
    /** @return list<TaskDefinition> */
    public function tasks(): array { return $this->tasks; }
}

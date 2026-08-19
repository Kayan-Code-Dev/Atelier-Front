<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Infrastructure\InMemory;

use DressnMore\Aos\Workflow\Domain\Repository\WorkflowRepositoryInterface;
use DressnMore\Aos\Workflow\Domain\Trigger\TriggerType;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowDefinition;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowId;

final class InMemoryWorkflowRepository implements WorkflowRepositoryInterface
{
    /** @var array<string, WorkflowDefinition> */
    private array $items = [];
    /** @var array<string, string> */
    private array $triggerIndex = [];

    public function save(WorkflowDefinition $workflow): void
    {
        $id = $workflow->id()->toString();
        $this->items[$id] = $workflow;
        $this->triggerIndex[$workflow->trigger()->value] = $id;
    }

    public function findById(WorkflowId $id): ?WorkflowDefinition
    {
        return $this->items[$id->toString()] ?? null;
    }

    public function findByTrigger(TriggerType $trigger): ?WorkflowDefinition
    {
        $id = $this->triggerIndex[$trigger->value] ?? null;
        return $id ? ($this->items[$id] ?? null) : null;
    }
}

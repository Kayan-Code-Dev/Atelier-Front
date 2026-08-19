<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Repository;

use DressnMore\Aos\Workflow\Domain\Trigger\TriggerType;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowDefinition;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowId;

interface WorkflowRepositoryInterface
{
    public function save(WorkflowDefinition $workflow): void;
    public function findById(WorkflowId $id): ?WorkflowDefinition;
    public function findByTrigger(TriggerType $trigger): ?WorkflowDefinition;
}

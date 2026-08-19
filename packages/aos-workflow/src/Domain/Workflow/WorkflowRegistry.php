<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Workflow;

use DressnMore\Aos\Workflow\Domain\Repository\WorkflowRepositoryInterface;
use DressnMore\Aos\Workflow\Domain\Trigger\TriggerType;

final class WorkflowRegistry
{
    public function __construct(private readonly WorkflowRepositoryInterface $repository) {}

    public function register(WorkflowDefinition $workflow): void
    {
        $this->repository->save($workflow);
    }

    public function forTrigger(TriggerType $trigger): ?WorkflowDefinition
    {
        return $this->repository->findByTrigger($trigger);
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Workflow;

final class WorkflowManager
{
    public function __construct(private readonly WorkflowRegistry $registry) {}

    public function register(WorkflowDefinition $workflow): void
    {
        $this->registry->register($workflow);
    }
}

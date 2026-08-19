<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Infrastructure\Bootstrap;

use DressnMore\Aos\Workflow\Domain\Factory\WorkflowFactory;
use DressnMore\Aos\Workflow\Domain\Task\TaskDefinition;
use DressnMore\Aos\Workflow\Domain\Task\TaskType;
use DressnMore\Aos\Workflow\Domain\Trigger\TriggerType;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowRegistry;
use DressnMore\Aos\Workflow\Domain\Workflow\WorkflowType;

final class BuiltinWorkflowCatalogBootstrap
{
    public function __construct(
        private readonly WorkflowFactory $factory,
        private readonly WorkflowRegistry $registry,
    ) {}

    public function seed(): void
    {
        $workflow = $this->factory->create(
            'Default Incoming Message Workflow',
            WorkflowType::Conversation,
            TriggerType::IncomingMessage,
            [
                new TaskDefinition('t1', TaskType::ConditionTask, ['result' => true]),
                new TaskDefinition('t2', TaskType::SequentialTask),
                new TaskDefinition('t3', TaskType::NotificationTask),
            ],
        );
        $this->registry->register($workflow);
    }
}

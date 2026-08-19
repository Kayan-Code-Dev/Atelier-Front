<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Workflow;

use DateTimeImmutable;

final class WorkflowScheduler
{
    /** @var array<string, DateTimeImmutable> */
    private array $queue = [];

    public function schedule(WorkflowId $id, DateTimeImmutable $runAt): void
    {
        $this->queue[$id->toString()] = $runAt;
    }

    /**
     * @return array<string, DateTimeImmutable>
     */
    public function queue(): array
    {
        return $this->queue;
    }
}

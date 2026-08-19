<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Monitoring;

final class WorkflowMetrics
{
    private int $started = 0;
    private int $completed = 0;
    private int $failed = 0;

    public function incStarted(): void { $this->started++; }
    public function incCompleted(): void { $this->completed++; }
    public function incFailed(): void { $this->failed++; }

    /**
     * @return array{started:int,completed:int,failed:int}
     */
    public function snapshot(): array
    {
        return ['started' => $this->started, 'completed' => $this->completed, 'failed' => $this->failed];
    }
}

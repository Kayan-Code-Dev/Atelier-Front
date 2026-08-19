<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Task;

final class Job
{
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $name,
        private readonly string $schedule = '',
        private readonly string $status = 'idle',
    ) {}

    public function id(): string { return $this->id; }
    public function tenantId(): string { return $this->tenantId; }
    public function name(): string { return $this->name; }
    public function schedule(): string { return $this->schedule; }
    public function status(): string { return $this->status; }
}

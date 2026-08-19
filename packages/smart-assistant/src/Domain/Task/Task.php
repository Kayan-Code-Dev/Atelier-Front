<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Task;

final class Task
{
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $type,
        private readonly string $status = 'pending',
    ) {}

    public function id(): string { return $this->id; }
    public function tenantId(): string { return $this->tenantId; }
    public function type(): string { return $this->type; }
    public function status(): string { return $this->status; }
}

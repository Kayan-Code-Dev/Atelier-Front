<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Automation;

final class Automation
{
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $name,
        private readonly string $status = 'stopped',
        private readonly ?string $workflowId = null,
    ) {}

    public function id(): string { return $this->id; }
    public function tenantId(): string { return $this->tenantId; }
    public function name(): string { return $this->name; }
    public function status(): string { return $this->status; }
    public function workflowId(): ?string { return $this->workflowId; }
}

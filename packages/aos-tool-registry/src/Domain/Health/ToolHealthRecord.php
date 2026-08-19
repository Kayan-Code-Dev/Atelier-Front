<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Health;

final class ToolHealthRecord
{
    public function __construct(
        private readonly string $toolName,
        private readonly bool $healthy,
        private readonly string $message = '',
        private readonly string $checkedAt = '',
    ) {}

    public function toolName(): string { return $this->toolName; }
    public function healthy(): bool { return $this->healthy; }
    public function message(): string { return $this->message; }
    public function checkedAt(): string { return $this->checkedAt; }
}

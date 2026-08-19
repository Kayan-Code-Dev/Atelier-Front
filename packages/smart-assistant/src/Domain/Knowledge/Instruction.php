<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Knowledge;

final class Instruction
{
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $scope,
        private readonly string $body,
    ) {}

    public function id(): string { return $this->id; }
    public function tenantId(): string { return $this->tenantId; }
    public function scope(): string { return $this->scope; }
    public function body(): string { return $this->body; }
}

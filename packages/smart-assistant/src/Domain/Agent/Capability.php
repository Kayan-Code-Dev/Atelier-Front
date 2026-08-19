<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Agent;

final class Capability
{
    public function __construct(
        private readonly string $id,
        private readonly string $agentType,
        private readonly string $name,
        private readonly string $description = '',
    ) {}

    public function id(): string { return $this->id; }
    public function agentType(): string { return $this->agentType; }
    public function name(): string { return $this->name; }
    public function description(): string { return $this->description; }
}

<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Agent;

final class Agent
{
    /**
     * @param list<string> $capabilityIds
     */
    public function __construct(
        private readonly string $id,
        private readonly string $type,
        private readonly string $name,
        private readonly array $capabilityIds = [],
        private readonly bool $active = true,
    ) {}

    public function id(): string { return $this->id; }
    public function type(): string { return $this->type; }
    public function name(): string { return $this->name; }
    /** @return list<string> */
    public function capabilityIds(): array { return $this->capabilityIds; }
    public function active(): bool { return $this->active; }
}

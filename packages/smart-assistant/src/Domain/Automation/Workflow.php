<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Automation;

final class Workflow
{
    /**
     * @param list<string> $stepIds
     */
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $name,
        private readonly array $stepIds = [],
    ) {}

    public function id(): string { return $this->id; }
    public function tenantId(): string { return $this->tenantId; }
    public function name(): string { return $this->name; }
    /** @return list<string> */
    public function stepIds(): array { return $this->stepIds; }
}

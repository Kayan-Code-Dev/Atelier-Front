<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Memory;

final class MemoryProfile
{
    /**
     * @param array<string, mixed> $attributes metadata only
     */
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly ?string $customerId,
        private readonly array $attributes = [],
    ) {}

    public function id(): string { return $this->id; }
    public function tenantId(): string { return $this->tenantId; }
    public function customerId(): ?string { return $this->customerId; }
    /** @return array<string, mixed> */
    public function attributes(): array { return $this->attributes; }
}

<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Reporting;

final class Report
{
    /**
     * @param array<string, mixed> $metrics
     */
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $type,
        private readonly array $metrics = [],
    ) {}

    public function id(): string { return $this->id; }
    public function tenantId(): string { return $this->tenantId; }
    public function type(): string { return $this->type; }
    /** @return array<string, mixed> */
    public function metrics(): array { return $this->metrics; }
}

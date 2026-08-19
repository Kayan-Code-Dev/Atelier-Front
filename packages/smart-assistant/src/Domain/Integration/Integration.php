<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Integration;

final class Integration
{
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $providerId,
        private readonly string $status = 'inactive',
    ) {}

    public function id(): string { return $this->id; }
    public function tenantId(): string { return $this->tenantId; }
    public function providerId(): string { return $this->providerId; }
    public function status(): string { return $this->status; }
}

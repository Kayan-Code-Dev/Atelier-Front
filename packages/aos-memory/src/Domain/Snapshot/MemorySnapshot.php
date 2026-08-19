<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Snapshot;

use DateTimeImmutable;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;

final class MemorySnapshot
{
    /**
     * @param  list<MemoryRecord>  $records
     * @param  array<string, scalar|null>  $metadata
     */
    public function __construct(
        private readonly SnapshotKind $kind,
        private readonly string $tenantId,
        private readonly ?string $customerId,
        private readonly ?string $conversationId,
        private readonly array $records,
        private readonly string $digest,
        private readonly DateTimeImmutable $generatedAt = new DateTimeImmutable(),
        private readonly array $metadata = [],
    ) {}

    public function kind(): SnapshotKind
    {
        return $this->kind;
    }

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function customerId(): ?string
    {
        return $this->customerId;
    }

    public function conversationId(): ?string
    {
        return $this->conversationId;
    }

    /**
     * @return list<MemoryRecord>
     */
    public function records(): array
    {
        return $this->records;
    }

    public function digest(): string
    {
        return $this->digest;
    }

    public function generatedAt(): DateTimeImmutable
    {
        return $this->generatedAt;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind->value,
            'tenant_id' => $this->tenantId,
            'customer_id' => $this->customerId,
            'conversation_id' => $this->conversationId,
            'digest' => $this->digest,
            'generated_at' => $this->generatedAt->format(DATE_ATOM),
            'count' => count($this->records),
            'records' => array_map(static fn (MemoryRecord $r): array => $r->toArray(), $this->records),
            'metadata' => $this->metadata,
        ];
    }
}

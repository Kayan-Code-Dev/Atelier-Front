<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Snapshot;

use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;
use DressnMore\Aos\Memory\Domain\Memory\MemoryType;
use DressnMore\Aos\Memory\Domain\Ranking\MemoryRanker;
use DressnMore\Aos\Memory\Domain\Repository\MemoryStoreInterface;
use DressnMore\Aos\Memory\Domain\Specifications\ActiveMemorySpecification;

final class MemorySnapshotFactory
{
    public function __construct(
        private readonly MemoryStoreInterface $store,
        private readonly MemoryRanker $ranker = new MemoryRanker(),
        private readonly ActiveMemorySpecification $active = new ActiveMemorySpecification(),
    ) {}

    public function conversation(string $tenantId, string $conversationId, ?string $customerId = null, int $limit = 30): MemorySnapshot
    {
        $records = $this->filter($this->store->findByScope(
            $tenantId,
            $customerId,
            $conversationId,
            [],
            $limit * 2,
        ), $limit);

        return $this->make(SnapshotKind::Conversation, $tenantId, $customerId, $conversationId, $records);
    }

    public function customer(string $tenantId, string $customerId, int $limit = 40): MemorySnapshot
    {
        $records = $this->filter($this->store->findByScope(
            $tenantId,
            $customerId,
            null,
            [MemoryType::Customer, MemoryType::Preference, MemoryType::Episodic, MemoryType::LongTerm],
            $limit * 2,
        ), $limit);

        return $this->make(SnapshotKind::Customer, $tenantId, $customerId, null, $records);
    }

    public function business(string $tenantId, int $limit = 40): MemorySnapshot
    {
        $records = $this->filter($this->store->findByScope(
            $tenantId,
            null,
            null,
            [MemoryType::Business, MemoryType::Operational, MemoryType::LongTerm],
            $limit * 2,
        ), $limit);

        return $this->make(SnapshotKind::Business, $tenantId, null, null, $records);
    }

    /**
     * @param  list<MemoryRecord>  $records
     * @return list<MemoryRecord>
     */
    private function filter(array $records, int $limit): array
    {
        $active = array_values(array_filter(
            $records,
            fn (MemoryRecord $r): bool => $this->active->isSatisfiedBy($r)
        ));

        return array_slice($this->ranker->rank($active), 0, $limit);
    }

    /**
     * @param  list<MemoryRecord>  $records
     */
    private function make(
        SnapshotKind $kind,
        string $tenantId,
        ?string $customerId,
        ?string $conversationId,
        array $records,
    ): MemorySnapshot {
        $digestParts = array_map(
            static fn (MemoryRecord $r): string => $r->type()->value.':'.$r->content(),
            $records
        );

        return new MemorySnapshot(
            $kind,
            $tenantId,
            $customerId,
            $conversationId,
            $records,
            hash('sha256', implode('|', $digestParts)),
            metadata: ['count' => count($records)],
        );
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Repository;

use DressnMore\Aos\Memory\Domain\Memory\MemoryId;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;
use DressnMore\Aos\Memory\Domain\Memory\MemoryType;

/**
 * Storage port — swapable without changing Domain.
 */
interface MemoryStoreInterface
{
    public function save(MemoryRecord $record): void;

    public function find(MemoryId $id): ?MemoryRecord;

    /**
     * @param  list<MemoryType>  $types
     * @return list<MemoryRecord>
     */
    public function findByScope(
        string $tenantId,
        ?string $customerId = null,
        ?string $conversationId = null,
        array $types = [],
        int $limit = 100,
    ): array;

    /**
     * @return list<MemoryRecord>
     */
    public function findByFingerprint(string $tenantId, string $fingerprint): array;

    public function delete(MemoryId $id): void;

    /**
     * @return list<MemoryRecord>
     */
    public function allActiveForTenant(string $tenantId): array;
}

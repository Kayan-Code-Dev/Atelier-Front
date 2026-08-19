<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Policies;

use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;
use DressnMore\Aos\Memory\Domain\Repository\MemoryStoreInterface;

final class MemoryExpirationManager
{
    public function __construct(
        private readonly MemoryStoreInterface $store,
    ) {}

    /**
     * @return list<MemoryRecord> Expired (and discarded) records
     */
    public function expireForTenant(string $tenantId): array
    {
        $expired = [];
        foreach ($this->store->allActiveForTenant($tenantId) as $record) {
            if ($record->isExpired() && ! $record->isDiscarded()) {
                $discarded = $record->discard();
                $this->store->save($discarded);
                $expired[] = $discarded;
            }
        }

        return $expired;
    }
}

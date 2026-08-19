<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Specifications;

use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;

final class TenantIsolationSpecification
{
    public function isSatisfiedBy(MemoryRecord $record, string $tenantId): bool
    {
        return $record->belongsToTenant($tenantId);
    }
}

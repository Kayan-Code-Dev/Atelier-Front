<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Specifications;

use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;

final class CustomerIsolationSpecification
{
    public function isSatisfiedBy(MemoryRecord $record, ?string $customerId): bool
    {
        if ($customerId === null) {
            return true;
        }

        // Customer-scoped records must match; tenant-level business memories may omit customer.
        if ($record->customerId() === null) {
            return true;
        }

        return $record->belongsToCustomer($customerId);
    }
}

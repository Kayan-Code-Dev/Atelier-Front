<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Specification;

use DressnMore\Aos\Communication\Domain\Message\NormalizedMessage;

final class TenantIsolationSpecification
{
    public function isSatisfiedBy(NormalizedMessage $message, ?string $expectedTenantId): bool
    {
        if ($expectedTenantId === null) {
            return true;
        }

        return $message->tenantId() === $expectedTenantId;
    }
}

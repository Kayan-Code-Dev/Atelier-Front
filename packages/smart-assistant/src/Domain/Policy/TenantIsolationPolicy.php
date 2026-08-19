<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Policy;

/**
 * Declares tenant-boundary rules without runtime enforcement engines.
 */
final class TenantIsolationPolicy
{
    public function assertSameTenant(string $expectedTenantId, string $actualTenantId): bool
    {
        return $expectedTenantId !== '' && $expectedTenantId === $actualTenantId;
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Tenant;

/**
 * Tenant slice for the Context Snapshot.
 */
final class TenantContext
{
    public function __construct(
        private readonly TenantId $tenantId,
        private readonly string $slug,
        private readonly ?string $displayName = null,
        private readonly ?BranchId $defaultBranchId = null,
    ) {}

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function displayName(): ?string
    {
        return $this->displayName;
    }

    public function defaultBranchId(): ?BranchId
    {
        return $this->defaultBranchId;
    }
}

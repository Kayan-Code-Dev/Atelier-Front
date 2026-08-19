<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Application;

use DressnMore\Aos\TenantAi\Contracts\PermissionProviderInterface;
use DressnMore\Aos\TenantAi\Domain\Permission\PermissionResolution;
use DressnMore\Aos\TenantAi\Domain\Policies\TenantAiPolicies;

final class PermissionResolver implements PermissionProviderInterface
{
    public function __construct(private readonly TenantAiPolicies $policies = new TenantAiPolicies()) {}

    public function resolve(string $userId, string $role, array $permissions, array $capabilities, array $tools): PermissionResolution
    {
        return new PermissionResolution($userId, $role, $permissions, $capabilities, $tools);
    }

    public function assertToolAllowed(PermissionResolution $resolution, string $toolName): void
    {
        $this->policies->assertPermission($resolution, $toolName);
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Contracts;

use DressnMore\Aos\TenantAi\Domain\Permission\PermissionResolution;

interface PermissionProviderInterface
{
    /**
     * @param list<string> $permissions
     * @param list<string> $capabilities
     * @param list<string> $tools
     */
    public function resolve(string $userId, string $role, array $permissions, array $capabilities, array $tools): PermissionResolution;
}

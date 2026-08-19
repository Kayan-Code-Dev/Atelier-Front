<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Authorization;

use DressnMore\Aos\Permissions\Domain\Capability\CapabilityCode;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityDefinition;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingModeCode;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionCode;

/**
 * Resolved permission slice for one authorization cycle.
 */
final class PermissionContext
{
    /**
     * @param  list<PermissionCode>  $permissions
     * @param  list<CapabilityCode>  $capabilities
     * @param  list<string>  $roles
     */
    public function __construct(
        private readonly array $permissions,
        private readonly array $capabilities,
        private readonly array $roles,
    ) {}

    /**
     * @return list<PermissionCode>
     */
    public function permissions(): array
    {
        return $this->permissions;
    }

    /**
     * @return list<CapabilityCode>
     */
    public function capabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * @return list<string>
     */
    public function roles(): array
    {
        return $this->roles;
    }

    public function hasPermission(string $code): bool
    {
        foreach ($this->permissions as $permission) {
            if ($permission->toString() === $code) {
                return true;
            }
        }

        return false;
    }

    public function hasCapability(string $code): bool
    {
        foreach ($this->capabilities as $capability) {
            if ($capability->toString() === $code) {
                return true;
            }
        }

        return false;
    }
}

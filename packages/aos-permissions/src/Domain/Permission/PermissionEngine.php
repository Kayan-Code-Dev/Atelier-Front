<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Permission;

use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationRequest;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityDefinition;

final class PermissionEngine
{
    public function __construct(
        private readonly PermissionRegistryInterface $registry,
    ) {}

    public function hasAllRequired(AuthorizationRequest $request, ?CapabilityDefinition $capability): bool
    {
        if ($capability === null) {
            return false;
        }

        foreach ($capability->requiredPermissions() as $required) {
            if (! in_array($required, $request->grantedPermissions(), true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Role-based shortcut: if role grants are empty, fall back to explicit permissions only.
     *
     * @param  list<string>  $rolePermissions
     */
    public function mergeRolePermissions(AuthorizationRequest $request, array $rolePermissions): array
    {
        return array_values(array_unique(array_merge($request->grantedPermissions(), $rolePermissions)));
    }

    public function isRegistered(PermissionCode $code): bool
    {
        return $this->registry->has($code);
    }
}

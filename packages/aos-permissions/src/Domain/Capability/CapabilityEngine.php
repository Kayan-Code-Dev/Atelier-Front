<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Capability;

use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationRequest;
use DressnMore\Aos\Permissions\Domain\Authorization\PermissionContext;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionCode;

final class CapabilityEngine
{
    public function __construct(
        private readonly CapabilityRegistryInterface $registry,
    ) {}

    public function resolveDefinition(CapabilityCode $code): ?CapabilityDefinition
    {
        return $this->registry->get($code);
    }

    public function isGranted(AuthorizationRequest $request): bool
    {
        return in_array(
            $request->requestedCapability()->toString(),
            $request->grantedCapabilities(),
            true
        );
    }

    /**
     * @return list<CapabilityCode>
     */
    public function resolveGranted(AuthorizationRequest $request): array
    {
        return array_map(
            static fn (string $c): CapabilityCode => CapabilityCode::fromString($c),
            $request->grantedCapabilities()
        );
    }

    public function buildPermissionContext(AuthorizationRequest $request): PermissionContext
    {
        $permissions = array_map(
            static fn (string $p): PermissionCode => PermissionCode::fromString($p),
            $request->grantedPermissions()
        );

        return new PermissionContext(
            $permissions,
            $this->resolveGranted($request),
            $request->roles(),
        );
    }
}

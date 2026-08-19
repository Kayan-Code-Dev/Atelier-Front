<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Factories;

use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationRequest;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityCode;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingModeCode;
use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;

final class AuthorizationRequestFactory
{
    /**
     * @param  list<string>  $grantedCapabilities
     * @param  list<string>  $grantedPermissions
     * @param  list<string>  $roles
     * @param  array<string, scalar|null>  $attributes
     */
    public function make(
        string $capability,
        string $mode,
        array $grantedCapabilities,
        array $grantedPermissions = [],
        array $roles = [],
        ?RiskLevel $risk = null,
        ?string $tenantId = null,
        array $attributes = [],
    ): AuthorizationRequest {
        return AuthorizationRequest::create(
            CapabilityCode::fromString($capability),
            OperatingModeCode::fromString($mode),
            $grantedCapabilities,
            $grantedPermissions,
            $roles,
            $risk,
            $attributes,
            $tenantId,
        );
    }
}

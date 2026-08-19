<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Authorization;

use DressnMore\Aos\Permissions\Domain\Capability\CapabilityCode;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingModeCode;
use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;

/**
 * Immutable authorization request entering the Permission Engine.
 */
final class AuthorizationRequest
{
    /**
     * @param  list<string>  $grantedCapabilities
     * @param  list<string>  $grantedPermissions
     * @param  list<string>  $roles
     * @param  array<string, scalar|null>  $attributes
     */
    public function __construct(
        private readonly string $correlationId,
        private readonly CapabilityCode $requestedCapability,
        private readonly OperatingModeCode $operatingMode,
        private readonly array $grantedCapabilities = [],
        private readonly array $grantedPermissions = [],
        private readonly array $roles = [],
        private readonly ?RiskLevel $declaredRisk = null,
        private readonly array $attributes = [],
        private readonly ?string $tenantId = null,
        private readonly ?string $action = null,
    ) {}

    /**
     * @param  list<string>  $grantedCapabilities
     * @param  list<string>  $grantedPermissions
     * @param  list<string>  $roles
     * @param  array<string, scalar|null>  $attributes
     */
    public static function create(
        CapabilityCode $requestedCapability,
        OperatingModeCode $operatingMode,
        array $grantedCapabilities = [],
        array $grantedPermissions = [],
        array $roles = [],
        ?RiskLevel $declaredRisk = null,
        array $attributes = [],
        ?string $tenantId = null,
        ?string $action = null,
        ?string $correlationId = null,
    ): self {
        return new self(
            $correlationId ?? bin2hex(random_bytes(12)),
            $requestedCapability,
            $operatingMode,
            $grantedCapabilities,
            $grantedPermissions,
            $roles,
            $declaredRisk,
            $attributes,
            $tenantId,
            $action,
        );
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function requestedCapability(): CapabilityCode
    {
        return $this->requestedCapability;
    }

    public function operatingMode(): OperatingModeCode
    {
        return $this->operatingMode;
    }

    /**
     * @return list<string>
     */
    public function grantedCapabilities(): array
    {
        return $this->grantedCapabilities;
    }

    /**
     * @return list<string>
     */
    public function grantedPermissions(): array
    {
        return $this->grantedPermissions;
    }

    /**
     * @return list<string>
     */
    public function roles(): array
    {
        return $this->roles;
    }

    public function declaredRisk(): ?RiskLevel
    {
        return $this->declaredRisk;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    public function action(): ?string
    {
        return $this->action;
    }
}

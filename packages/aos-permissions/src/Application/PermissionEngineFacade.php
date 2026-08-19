<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Application;

use DressnMore\Aos\Permissions\Domain\Approval\ApprovalEngine;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationRequest;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityCode;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityEngine;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityRegistryInterface;
use DressnMore\Aos\Permissions\Domain\Decision\DecisionContext;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingModeCode;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingModeManager;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionEngine;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionRegistryInterface;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyEngine;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyRegistryInterface;
use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;

/**
 * Permission Engine façade — official guard for AI actions inside AOS.
 */
final class PermissionEngineFacade
{
    public function __construct(
        private readonly AuthorizationManager $authorizationManager,
        private readonly CapabilityEngine $capabilityEngine,
        private readonly PermissionEngine $permissionEngine,
        private readonly PolicyEngine $policyEngine,
        private readonly ApprovalEngine $approvalEngine,
        private readonly OperatingModeManager $modeManager,
        private readonly CapabilityRegistryInterface $capabilityRegistry,
        private readonly PermissionRegistryInterface $permissionRegistry,
        private readonly PolicyRegistryInterface $policyRegistry,
    ) {}

    public function authorize(AuthorizationRequest $request): DecisionContext
    {
        return $this->authorizationManager->authorize($request);
    }

    /**
     * @param  list<string>  $grantedCapabilities
     * @param  list<string>  $grantedPermissions
     * @param  list<string>  $roles
     */
    public function authorizeCapability(
        string $capability,
        string $operatingMode,
        array $grantedCapabilities,
        array $grantedPermissions = [],
        array $roles = [],
        ?RiskLevel $declaredRisk = null,
        ?string $tenantId = null,
    ): DecisionContext {
        $request = AuthorizationRequest::create(
            CapabilityCode::fromString($capability),
            OperatingModeCode::fromString($operatingMode),
            $grantedCapabilities,
            $grantedPermissions,
            $roles,
            $declaredRisk,
            [],
            $tenantId,
        );

        return $this->authorize($request);
    }

    public function authorizationManager(): AuthorizationManager
    {
        return $this->authorizationManager;
    }

    public function capabilityEngine(): CapabilityEngine
    {
        return $this->capabilityEngine;
    }

    public function permissionEngine(): PermissionEngine
    {
        return $this->permissionEngine;
    }

    public function policyEngine(): PolicyEngine
    {
        return $this->policyEngine;
    }

    public function approvalEngine(): ApprovalEngine
    {
        return $this->approvalEngine;
    }

    public function modeManager(): OperatingModeManager
    {
        return $this->modeManager;
    }

    public function capabilityRegistry(): CapabilityRegistryInterface
    {
        return $this->capabilityRegistry;
    }

    public function permissionRegistry(): PermissionRegistryInterface
    {
        return $this->permissionRegistry;
    }

    public function policyRegistry(): PolicyRegistryInterface
    {
        return $this->policyRegistry;
    }
}

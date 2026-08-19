<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Application;

use DressnMore\Aos\Permissions\Domain\Approval\ApprovalEngine;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationPipeline;
use DressnMore\Aos\Permissions\Domain\Authorization\Stages\DecisionAndApprovalStage;
use DressnMore\Aos\Permissions\Domain\Authorization\Stages\EvaluatePoliciesStage;
use DressnMore\Aos\Permissions\Domain\Authorization\Stages\EvaluateRiskStage;
use DressnMore\Aos\Permissions\Domain\Authorization\Stages\LoadContextStage;
use DressnMore\Aos\Permissions\Domain\Authorization\Stages\ResolveCapabilitiesStage;
use DressnMore\Aos\Permissions\Domain\Authorization\Stages\ResolveOperatingModeStage;
use DressnMore\Aos\Permissions\Domain\Authorization\Stages\ResolvePermissionsStage;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityEngine;
use DressnMore\Aos\Permissions\Domain\Decision\DecisionEngine;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingModeManager;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionEngine;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyEngine;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyResolver;
use DressnMore\Aos\Permissions\Domain\Risk\RiskEvaluator;

final class AuthorizationPipelineFactory
{
    public function __construct(
        private readonly CapabilityEngine $capabilityEngine,
        private readonly PermissionEngine $permissionEngine,
        private readonly OperatingModeManager $modeManager,
        private readonly PolicyEngine $policyEngine,
        private readonly PolicyResolver $policyResolver,
        private readonly RiskEvaluator $riskEvaluator,
        private readonly DecisionEngine $decisionEngine,
        private readonly ApprovalEngine $approvalEngine,
    ) {}

    public function create(): AuthorizationPipeline
    {
        return new AuthorizationPipeline([
            new LoadContextStage($this->capabilityEngine),
            new ResolveOperatingModeStage($this->modeManager),
            new ResolveCapabilitiesStage($this->capabilityEngine),
            new ResolvePermissionsStage($this->permissionEngine),
            new EvaluatePoliciesStage($this->policyEngine, $this->policyResolver),
            new EvaluateRiskStage($this->riskEvaluator),
            new DecisionAndApprovalStage($this->decisionEngine, $this->approvalEngine, $this->policyEngine),
        ]);
    }
}

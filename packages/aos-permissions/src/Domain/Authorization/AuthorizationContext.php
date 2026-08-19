<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Authorization;

use DressnMore\Aos\Permissions\Domain\Approval\ApprovalRequest;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityDefinition;
use DressnMore\Aos\Permissions\Domain\Decision\AuthorizationOutcome;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingModeCode;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyEvaluationResult;
use DressnMore\Aos\Permissions\Domain\Risk\RiskEvaluation;

/**
 * Mutable bag accumulated during the Authorization Pipeline.
 */
final class AuthorizationContext
{
    private ?OperatingModeCode $resolvedMode = null;

    private ?PermissionContext $permissionContext = null;

    private ?CapabilityDefinition $capabilityDefinition = null;

    private ?RiskEvaluation $riskEvaluation = null;

    private ?PolicyEvaluationResult $policyEvaluation = null;

    private ?AuthorizationOutcome $outcome = null;

    private ?ApprovalRequest $approvalRequest = null;

    private string $reason = '';

    /** @var list<string> */
    private array $stages = [];

    public function __construct(
        private readonly AuthorizationRequest $request,
    ) {}

    public function request(): AuthorizationRequest
    {
        return $this->request;
    }

    public function markStage(string $stage): void
    {
        $this->stages[] = $stage;
    }

    /**
     * @return list<string>
     */
    public function stages(): array
    {
        return $this->stages;
    }

    public function setResolvedMode(OperatingModeCode $mode): void
    {
        $this->resolvedMode = $mode;
    }

    public function resolvedMode(): ?OperatingModeCode
    {
        return $this->resolvedMode;
    }

    public function setPermissionContext(PermissionContext $context): void
    {
        $this->permissionContext = $context;
    }

    public function permissionContext(): ?PermissionContext
    {
        return $this->permissionContext;
    }

    public function setCapabilityDefinition(?CapabilityDefinition $definition): void
    {
        $this->capabilityDefinition = $definition;
    }

    public function capabilityDefinition(): ?CapabilityDefinition
    {
        return $this->capabilityDefinition;
    }

    public function setRiskEvaluation(RiskEvaluation $evaluation): void
    {
        $this->riskEvaluation = $evaluation;
    }

    public function riskEvaluation(): ?RiskEvaluation
    {
        return $this->riskEvaluation;
    }

    public function setPolicyEvaluation(PolicyEvaluationResult $evaluation): void
    {
        $this->policyEvaluation = $evaluation;
    }

    public function policyEvaluation(): ?PolicyEvaluationResult
    {
        return $this->policyEvaluation;
    }

    public function setOutcome(AuthorizationOutcome $outcome, string $reason = ''): void
    {
        $this->outcome = $outcome;
        if ($reason !== '') {
            $this->reason = $reason;
        }
    }

    public function outcome(): ?AuthorizationOutcome
    {
        return $this->outcome;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function setApprovalRequest(ApprovalRequest $request): void
    {
        $this->approvalRequest = $request;
    }

    public function approvalRequest(): ?ApprovalRequest
    {
        return $this->approvalRequest;
    }
}

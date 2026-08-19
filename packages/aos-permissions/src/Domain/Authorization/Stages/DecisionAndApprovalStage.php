<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Authorization\Stages;

use DressnMore\Aos\Permissions\Domain\Approval\ApprovalEngine;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationContext;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationStage;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationStageInterface;
use DressnMore\Aos\Permissions\Domain\Decision\AuthorizationOutcome;
use DressnMore\Aos\Permissions\Domain\Decision\DecisionEngine;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyEngine;
use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;

final class DecisionAndApprovalStage implements AuthorizationStageInterface
{
    public function __construct(
        private readonly DecisionEngine $decisionEngine,
        private readonly ApprovalEngine $approvalEngine,
        private readonly PolicyEngine $policyEngine,
    ) {}

    public function name(): AuthorizationStage
    {
        return AuthorizationStage::Decision;
    }

    public function process(AuthorizationContext $context): void
    {
        $context->markStage(AuthorizationStage::NeedApproval->value);

        // Re-evaluate policies with final risk so minimumRisk thresholds are accurate.
        $mode = $context->resolvedMode() ?? $context->request()->operatingMode();
        $risk = $context->riskEvaluation()?->level() ?? RiskLevel::Medium;
        $context->setPolicyEvaluation($this->policyEngine->evaluate(
            $context->request()->requestedCapability(),
            $mode,
            $risk,
        ));

        $outcome = $this->decisionEngine->decide($context);

        if ($outcome === AuthorizationOutcome::ApprovalRequired
            || $outcome === AuthorizationOutcome::HumanEscalation
        ) {
            $approval = $this->approvalEngine->requestApproval(
                $context->request()->correlationId(),
                $context->request()->requestedCapability(),
                $risk,
            );
            $context->setApprovalRequest($approval);
        }
    }
}

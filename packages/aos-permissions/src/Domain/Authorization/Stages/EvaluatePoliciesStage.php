<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Authorization\Stages;

use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationContext;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationStage;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationStageInterface;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyEngine;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyResolver;
use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;

final class EvaluatePoliciesStage implements AuthorizationStageInterface
{
    public function __construct(
        private readonly PolicyEngine $policyEngine,
        private readonly PolicyResolver $policyResolver,
    ) {}

    public function name(): AuthorizationStage
    {
        return AuthorizationStage::EvaluatePolicies;
    }

    public function process(AuthorizationContext $context): void
    {
        $this->policyResolver->resolveApplicable($context);

        $mode = $context->resolvedMode() ?? $context->request()->operatingMode();
        $risk = $context->riskEvaluation()?->level()
            ?? $context->request()->declaredRisk()
            ?? $context->capabilityDefinition()?->defaultRisk()
            ?? RiskLevel::Medium;

        // Risk may not be evaluated yet — use provisional risk for policy matching.
        $evaluation = $this->policyEngine->evaluate(
            $context->request()->requestedCapability(),
            $mode,
            $risk,
        );
        $context->setPolicyEvaluation($evaluation);
    }
}

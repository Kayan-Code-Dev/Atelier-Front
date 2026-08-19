<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Authorization\Stages;

use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationContext;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationStage;
use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationStageInterface;
use DressnMore\Aos\Permissions\Domain\Risk\RiskEvaluator;

final class EvaluateRiskStage implements AuthorizationStageInterface
{
    public function __construct(
        private readonly RiskEvaluator $riskEvaluator,
    ) {}

    public function name(): AuthorizationStage
    {
        return AuthorizationStage::EvaluateRisk;
    }

    public function process(AuthorizationContext $context): void
    {
        $mode = $context->resolvedMode() ?? $context->request()->operatingMode();
        $evaluation = $this->riskEvaluator->evaluate(
            $context->capabilityDefinition(),
            $mode,
            $context->request()->declaredRisk(),
        );
        $context->setRiskEvaluation($evaluation);
    }
}

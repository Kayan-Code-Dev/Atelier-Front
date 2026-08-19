<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Risk;

use DressnMore\Aos\Permissions\Domain\Capability\CapabilityDefinition;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingMode;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingModeCode;

final class RiskEvaluator
{
    public function evaluate(
        ?CapabilityDefinition $capability,
        OperatingModeCode $mode,
        ?RiskLevel $declaredRisk = null,
    ): RiskEvaluation {
        $level = $declaredRisk
            ?? $capability?->defaultRisk()
            ?? RiskLevel::Medium;

        $requiresApproval = $capability?->requiresApprovalByDefault() ?? false;
        $requiresHuman = false;
        $reason = 'capability default risk';

        if ($declaredRisk !== null) {
            $reason = 'declared risk override';
        }

        if ($level === RiskLevel::Critical) {
            $requiresApproval = true;
            $requiresHuman = $mode->toBuiltin() === OperatingMode::FullAuto;
            $reason .= '; critical risk';
        } elseif ($level === RiskLevel::High) {
            $requiresApproval = true;
            $reason .= '; high risk';
        }

        if ($mode->toBuiltin() === OperatingMode::FullAuto && $level->atLeast(RiskLevel::High)) {
            $requiresHuman = true;
            $reason .= '; full_auto + high/critical';
        }

        return new RiskEvaluation($level, $reason, $requiresApproval, $requiresHuman);
    }
}

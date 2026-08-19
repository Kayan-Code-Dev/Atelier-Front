<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Decision;

use DressnMore\Aos\Permissions\Domain\Authorization\AuthorizationContext;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingMode;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingModeManager;
use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;

/**
 * Combines mode, capability, permission, policy, and risk into a final outcome.
 */
final class DecisionEngine
{
    public function __construct(
        private readonly OperatingModeManager $modeManager = new OperatingModeManager(),
    ) {}

    public function decide(AuthorizationContext $context): AuthorizationOutcome
    {
        $mode = $context->resolvedMode() ?? $context->request()->operatingMode();

        $hard = $this->modeManager->hardDenyOutcome($mode);
        if ($hard !== null) {
            $context->setOutcome($hard, 'operating mode constraint: '.$mode->toString());

            return $hard;
        }

        $permissionContext = $context->permissionContext();
        $capability = $context->request()->requestedCapability()->toString();

        if ($permissionContext === null || ! $permissionContext->hasCapability($capability)) {
            $outcome = AuthorizationOutcome::Denied;
            $context->setOutcome($outcome, 'capability not granted');

            return $outcome;
        }

        $definition = $context->capabilityDefinition();
        if ($definition !== null) {
            foreach ($definition->requiredPermissions() as $required) {
                if (! $permissionContext->hasPermission($required)) {
                    $outcome = AuthorizationOutcome::Denied;
                    $context->setOutcome($outcome, 'missing permission: '.$required);

                    return $outcome;
                }
            }
        }

        if ($mode->toBuiltin() === OperatingMode::ReadOnly
            && $definition !== null
            && $definition->defaultRisk()->atLeast(RiskLevel::Medium)
            && $this->looksLikeMutation($capability)
        ) {
            $outcome = AuthorizationOutcome::Denied;
            $context->setOutcome($outcome, 'read_only mode blocks mutating capability');

            return $outcome;
        }

        $risk = $context->riskEvaluation();
        if ($risk !== null && $risk->requiresHuman()) {
            $outcome = AuthorizationOutcome::HumanEscalation;
            $context->setOutcome($outcome, $risk->reason());

            return $outcome;
        }

        $policyEffect = $context->policyEvaluation()?->dominantEffect();
        if ($policyEffect !== null && $policyEffect !== AuthorizationOutcome::Authorized) {
            $context->setOutcome($policyEffect, $context->policyEvaluation()?->reason() ?? 'policy effect');

            return $policyEffect;
        }

        if ($risk !== null && $risk->requiresApproval()) {
            $outcome = AuthorizationOutcome::ApprovalRequired;
            $context->setOutcome($outcome, $risk->reason());

            return $outcome;
        }

        $outcome = AuthorizationOutcome::Authorized;
        $context->setOutcome($outcome, 'all checks passed');

        return $outcome;
    }

    private function looksLikeMutation(string $capability): bool
    {
        foreach (['create_', 'update_', 'cancel_', 'issue_', 'send_', 'assign_', 'execute_', 'transfer_', 'approve_'] as $prefix) {
            if (str_starts_with($capability, $prefix)) {
                return true;
            }
        }

        return false;
    }
}

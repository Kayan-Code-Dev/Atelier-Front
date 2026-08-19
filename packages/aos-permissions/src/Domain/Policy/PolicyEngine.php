<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Policy;

use DressnMore\Aos\Permissions\Domain\Capability\CapabilityCode;
use DressnMore\Aos\Permissions\Domain\Decision\AuthorizationOutcome;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingModeCode;
use DressnMore\Aos\Permissions\Domain\Risk\RiskLevel;

final class PolicyEngine
{
    public function __construct(
        private readonly PolicyRegistryInterface $registry,
    ) {}

    public function evaluate(
        CapabilityCode $capability,
        OperatingModeCode $mode,
        RiskLevel $risk,
    ): PolicyEvaluationResult {
        $matched = [];
        foreach ($this->registry->all() as $policy) {
            if ($policy->matches($capability, $mode, $risk)) {
                $matched[] = $policy;
            }
        }

        if ($matched === []) {
            return PolicyEvaluationResult::none();
        }

        usort(
            $matched,
            static fn (PolicyDefinition $a, PolicyDefinition $b): int => $a->priority() <=> $b->priority()
        );

        // Lowest priority number wins; Deny/Human beats Approval beats Authorized.
        $dominant = $matched[0]->effect();
        foreach ($matched as $policy) {
            $dominant = $this->mergeEffects($dominant, $policy->effect());
        }

        return PolicyEvaluationResult::of(
            $dominant,
            $matched,
            sprintf('matched %d policies; dominant=%s', count($matched), $dominant->value),
        );
    }

    private function mergeEffects(AuthorizationOutcome $current, AuthorizationOutcome $next): AuthorizationOutcome
    {
        $rank = static fn (AuthorizationOutcome $o): int => match ($o) {
            AuthorizationOutcome::Denied => 50,
            AuthorizationOutcome::HumanEscalation => 40,
            AuthorizationOutcome::RetryLater => 30,
            AuthorizationOutcome::ApprovalRequired => 20,
            AuthorizationOutcome::Authorized => 10,
        };

        return $rank($next) > $rank($current) ? $next : $current;
    }
}

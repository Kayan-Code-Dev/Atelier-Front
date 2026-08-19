<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Specifications;

use DressnMore\Aos\Permissions\Domain\Decision\AuthorizationOutcome;
use DressnMore\Aos\Permissions\Domain\Decision\DecisionContext;

final class DecisionRequiresApproval
{
    public function isSatisfiedBy(DecisionContext $decision): bool
    {
        return in_array($decision->outcome(), [
            AuthorizationOutcome::ApprovalRequired,
            AuthorizationOutcome::HumanEscalation,
        ], true);
    }
}

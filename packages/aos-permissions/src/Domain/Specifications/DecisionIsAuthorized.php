<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Specifications;

use DressnMore\Aos\Permissions\Domain\Decision\AuthorizationOutcome;
use DressnMore\Aos\Permissions\Domain\Decision\DecisionContext;

final class DecisionIsAuthorized
{
    public function isSatisfiedBy(DecisionContext $decision): bool
    {
        return $decision->outcome() === AuthorizationOutcome::Authorized;
    }
}

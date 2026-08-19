<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Specifications;

use DressnMore\Aos\Prompts\Domain\Prompt\TokenBudget;

final class WithinTokenBudgetSpecification
{
    public function isSatisfiedBy(TokenBudget $budget): bool
    {
        return ! $budget->exceeds();
    }
}

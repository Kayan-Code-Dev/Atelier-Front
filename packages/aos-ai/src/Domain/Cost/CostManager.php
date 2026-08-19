<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Cost;

use DressnMore\Aos\Ai\Domain\Model\ModelDescriptor;
use DressnMore\Aos\Ai\Domain\Policies\BudgetPolicy;
use DressnMore\Aos\Ai\Domain\Token\TokenUsage;

final class CostManager
{
    public function __construct(
        private readonly BudgetPolicy $budget = new BudgetPolicy(),
    ) {}

    public function calculate(ModelDescriptor $model, TokenUsage $usage): float
    {
        return round($this->budget->estimateCostUsd(
            $model,
            $usage->promptTokens(),
            $usage->completionTokens(),
        ), 6);
    }
}

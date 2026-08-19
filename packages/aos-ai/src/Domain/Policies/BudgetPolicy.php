<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Policies;

use DressnMore\Aos\Ai\Domain\Model\ModelDescriptor;
use DressnMore\Aos\Ai\Domain\Provider\ProviderDescriptor;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;

final class BudgetPolicy
{
    public function allows(ModelDescriptor $model, AiRequest $request): bool
    {
        $estimated = (($request->maxTokens() / 1000) * $model->costPer1kOutputTokens())
            + (0.5 * $model->costPer1kInputTokens());

        return $estimated <= $request->maxBudgetUsd();
    }

    public function estimateCostUsd(ModelDescriptor $model, int $promptTokens, int $completionTokens): float
    {
        return ($promptTokens / 1000) * $model->costPer1kInputTokens()
            + ($completionTokens / 1000) * $model->costPer1kOutputTokens();
    }
}

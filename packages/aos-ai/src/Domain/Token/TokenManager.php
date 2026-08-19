<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Token;

use DressnMore\Aos\Ai\Domain\Model\ModelDescriptor;
use DressnMore\Aos\Ai\Domain\Policies\BudgetPolicy;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;

final class TokenManager
{
    public function estimatePromptTokens(AiRequest $request): int
    {
        $text = $request->prompt().' '.$request->context();
        foreach ($request->conversation() as $turn) {
            $text .= ' '.($turn['content'] ?? '');
        }

        return max(1, (int) ceil(mb_strlen($text) / 4));
    }

    public function withinModelContext(ModelDescriptor $model, AiRequest $request): bool
    {
        return ($this->estimatePromptTokens($request) + $request->maxTokens()) <= $model->maxContextTokens();
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Policies;

use DressnMore\Aos\Ai\Domain\Provider\ProviderDescriptor;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;

final class CompliancePolicy
{
    public function allows(ProviderDescriptor $provider, AiRequest $request): bool
    {
        $requireOnPrem = (bool) ($request->metadata()['require_on_prem'] ?? false);
        if (! $requireOnPrem) {
            return true;
        }

        return in_array($provider->kind()->value, ['ollama', 'llama_cpp', 'vllm'], true);
    }
}

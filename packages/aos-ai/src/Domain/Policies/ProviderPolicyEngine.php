<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Policies;

use DressnMore\Aos\Ai\Domain\Model\ModelDescriptor;
use DressnMore\Aos\Ai\Domain\Provider\ProviderDescriptor;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;

final class ProviderPolicyEngine
{
    public function __construct(
        private readonly BudgetPolicy $budget = new BudgetPolicy(),
        private readonly LatencyPolicy $latency = new LatencyPolicy(),
        private readonly SecurityPolicy $security = new SecurityPolicy(),
        private readonly CompliancePolicy $compliance = new CompliancePolicy(),
        private readonly TenantPolicy $tenant = new TenantPolicy(),
    ) {}

    public function allowsProvider(ProviderDescriptor $provider, AiRequest $request): bool
    {
        return $provider->isEnabled()
            && $provider->health()->isUsable()
            && $this->security->allows($provider, $request)
            && $this->compliance->allows($provider, $request)
            && $this->tenant->allows($provider, $request);
    }

    public function allowsModel(ModelDescriptor $model, AiRequest $request): bool
    {
        return $model->isEnabled()
            && $this->budget->allows($model, $request)
            && $this->latency->allows($model, $request);
    }

    public function budget(): BudgetPolicy
    {
        return $this->budget;
    }

    public function latency(): LatencyPolicy
    {
        return $this->latency;
    }
}

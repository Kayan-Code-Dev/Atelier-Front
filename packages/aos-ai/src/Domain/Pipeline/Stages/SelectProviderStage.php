<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Pipeline\Stages;

use DressnMore\Aos\Ai\Domain\Capability\CapabilityRegistry;
use DressnMore\Aos\Ai\Domain\Health\ProviderHealthMonitor;
use DressnMore\Aos\Ai\Domain\Model\ModelResolver;
use DressnMore\Aos\Ai\Domain\Pipeline\AiPipelineBag;
use DressnMore\Aos\Ai\Domain\Pipeline\AiPipelineStage;
use DressnMore\Aos\Ai\Domain\Pipeline\AiPipelineStageInterface;
use DressnMore\Aos\Ai\Domain\Policies\ProviderPolicyEngine;
use DressnMore\Aos\Ai\Domain\Provider\ProviderResolver;
use DressnMore\Aos\Ai\Domain\Selection\ProviderSelection;
use DressnMore\Aos\Ai\Domain\Selection\ProviderSelector;

final class SelectProviderStage implements AiPipelineStageInterface
{
    public function __construct(
        private readonly CapabilityRegistry $capabilities,
        private readonly ProviderResolver $providerResolver,
        private readonly ModelResolver $modelResolver,
        private readonly ProviderSelector $selector,
        private readonly ProviderPolicyEngine $policies,
        private readonly ProviderHealthMonitor $health,
    ) {}

    public function name(): AiPipelineStage
    {
        return AiPipelineStage::ProviderSelection;
    }

    public function process(AiPipelineBag $bag): void
    {
        $required = $this->capabilities->resolveRequired($bag->request());
        $bag->setRequiredCapabilities($required);
        $bag->mark(AiPipelineStage::ResolveRequiredCapabilities->value);

        $providers = $this->providerResolver->resolveCandidates($bag->request(), $required);
        $bag->setProviderCandidates($providers);
        $bag->mark(AiPipelineStage::ProviderFiltering->value);

        $models = $this->modelResolver->resolveCandidates($bag->request(), $providers, $required);
        $bag->setModelCandidates($models);
        $bag->mark(AiPipelineStage::ModelFiltering->value);
        $bag->mark(AiPipelineStage::PolicyValidation->value);
        $bag->mark(AiPipelineStage::BudgetValidation->value);

        foreach ($providers as $provider) {
            $status = $this->health->probe($provider->id());
            if (! $status->isUsable()) {
                $bag->addRejection('unhealthy:'.$provider->id()->toString());
            }
        }
        $bag->mark(AiPipelineStage::HealthCheck->value);
        $bag->mark(AiPipelineStage::LatencyCheck->value);

        // Build ranked list for fallback.
        $ranked = [];
        $remainingProviders = $providers;
        $remainingModels = $models;
        while ($remainingProviders !== [] && $remainingModels !== []) {
            $pick = $this->selector->select($bag->request(), $required, $remainingProviders, $remainingModels);
            if ($pick === null) {
                break;
            }
            $ranked[] = $pick;
            $remainingProviders = array_values(array_filter(
                $remainingProviders,
                static fn ($p) => $p->id()->toString() !== $pick->provider()->id()->toString()
            ));
        }

        $bag->setRankedSelections($ranked);
        $bag->setSelection($ranked[0] ?? null);

        if ($bag->selection() === null) {
            $bag->addRejection('no_provider_selected');
        }
    }
}

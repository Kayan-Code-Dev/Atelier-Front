<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Application;

use DressnMore\Aos\Ai\Domain\Capability\CapabilityRegistry;
use DressnMore\Aos\Ai\Domain\Cost\CostManager;
use DressnMore\Aos\Ai\Domain\Fallback\FallbackManager;
use DressnMore\Aos\Ai\Domain\Health\ProviderHealthMonitor;
use DressnMore\Aos\Ai\Domain\Metrics\ProviderMetrics;
use DressnMore\Aos\Ai\Domain\Model\ModelResolver;
use DressnMore\Aos\Ai\Domain\Pipeline\AiPipeline;
use DressnMore\Aos\Ai\Domain\Pipeline\Stages\ExecuteAndNormalizeStage;
use DressnMore\Aos\Ai\Domain\Pipeline\Stages\SelectProviderStage;
use DressnMore\Aos\Ai\Domain\Policies\ProviderPolicyEngine;
use DressnMore\Aos\Ai\Domain\Provider\ProviderRegistryInterface;
use DressnMore\Aos\Ai\Domain\Provider\ProviderResolver;
use DressnMore\Aos\Ai\Domain\Retry\RetryManager;
use DressnMore\Aos\Ai\Domain\Selection\ProviderSelector;

final class AiPipelineFactory
{
    public function __construct(
        private readonly CapabilityRegistry $capabilities,
        private readonly ProviderResolver $providerResolver,
        private readonly ModelResolver $modelResolver,
        private readonly ProviderSelector $selector,
        private readonly ProviderPolicyEngine $policies,
        private readonly ProviderHealthMonitor $health,
        private readonly ProviderRegistryInterface $providers,
        private readonly RetryManager $retry,
        private readonly FallbackManager $fallback,
        private readonly ProviderMetrics $metrics,
        private readonly CostManager $cost,
    ) {}

    public function create(): AiPipeline
    {
        return new AiPipeline([
            new SelectProviderStage(
                $this->capabilities,
                $this->providerResolver,
                $this->modelResolver,
                $this->selector,
                $this->policies,
                $this->health,
            ),
            new ExecuteAndNormalizeStage(
                $this->providers,
                $this->retry,
                $this->fallback,
                $this->health,
                $this->metrics,
                $this->cost,
            ),
        ]);
    }
}

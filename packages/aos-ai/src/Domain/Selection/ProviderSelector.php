<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Selection;

use DressnMore\Aos\Ai\Domain\Capability\ModelCapability;
use DressnMore\Aos\Ai\Domain\Model\ModelDescriptor;
use DressnMore\Aos\Ai\Domain\Model\ModelRegistryInterface;
use DressnMore\Aos\Ai\Domain\Policies\ProviderPolicyEngine;
use DressnMore\Aos\Ai\Domain\Provider\ProviderDescriptor;
use DressnMore\Aos\Ai\Domain\Provider\ProviderRegistryInterface;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;

/**
 * Scores candidates by cost, latency, health, priority, capability fit.
 */
final class ProviderSelector
{
    public function __construct(
        private readonly ProviderRegistryInterface $providers,
        private readonly ModelRegistryInterface $models,
        private readonly ProviderPolicyEngine $policies,
    ) {}

    /**
     * @param  list<ModelCapability>  $required
     * @param  list<ProviderDescriptor>  $providerCandidates
     * @param  list<ModelDescriptor>  $modelCandidates
     */
    public function select(
        AiRequest $request,
        array $required,
        array $providerCandidates,
        array $modelCandidates,
    ): ?ProviderSelection {
        if ($request->preferredProviderId() !== null) {
            $preferredId = $request->preferredProviderId()->toString();
            $preferred = array_values(array_filter(
                $providerCandidates,
                static fn (ProviderDescriptor $p): bool => $p->id()->toString() === $preferredId
            ));
            if ($preferred !== []) {
                // Prefer matching provider first, keep others for fallback.
                $others = array_values(array_filter(
                    $providerCandidates,
                    static fn (ProviderDescriptor $p): bool => $p->id()->toString() !== $preferredId
                ));
                $providerCandidates = array_merge($preferred, $others);
            }
        }

        $scored = [];
        foreach ($providerCandidates as $provider) {
            if (! $provider->supportsAll($required) && ! $this->modelsCover($provider, $required, $modelCandidates)) {
                continue;
            }
            if (! $provider->health()->isUsable() || ! $provider->isEnabled()) {
                continue;
            }
            foreach ($modelCandidates as $model) {
                if ($model->providerId()->toString() !== $provider->id()->toString()) {
                    continue;
                }
                if (! $model->supportsAll($required)) {
                    continue;
                }
                if ($request->preferredModelId() !== null
                    && $model->id()->toString() !== $request->preferredModelId()->toString()) {
                    continue;
                }
                if (! $this->policies->allowsModel($model, $request)) {
                    continue;
                }

                $score = $this->score($provider, $model, $request);
                if ($request->preferredProviderId()?->toString() === $provider->id()->toString()) {
                    $score += 0.5;
                }
                $scored[] = new ProviderSelection($provider, $model, $score, 'cost+latency+priority');
            }
        }

        if ($scored === []) {
            return null;
        }

        usort($scored, static fn (ProviderSelection $a, ProviderSelection $b): int => $b->score() <=> $a->score());

        return $scored[0];
    }

    /**
     * @param  list<ModelCapability>  $required
     * @param  list<ModelDescriptor>  $models
     */
    private function modelsCover(ProviderDescriptor $provider, array $required, array $models): bool
    {
        foreach ($models as $model) {
            if ($model->providerId()->toString() === $provider->id()->toString() && $model->supportsAll($required)) {
                return true;
            }
        }

        return false;
    }

    private function score(ProviderDescriptor $provider, ModelDescriptor $model, AiRequest $request): float
    {
        $costScore = 1.0 / (1.0 + $model->costPer1kOutputTokens() * 1000);
        $latencyScore = 1.0 / (1.0 + ($model->typicalLatencyMs() / max(1, $request->maxLatencyMs())));
        $priorityScore = 1.0 / (1.0 + ($provider->priority() / 100));
        $healthBonus = $provider->health()->value === 'healthy' ? 0.15 : 0.0;

        return (0.35 * $costScore) + (0.30 * $latencyScore) + (0.20 * $priorityScore) + $healthBonus;
    }
}

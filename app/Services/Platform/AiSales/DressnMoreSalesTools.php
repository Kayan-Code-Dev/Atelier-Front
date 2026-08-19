<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

use App\Contracts\AiSales\DressnMoreSalesContext;
use App\Services\Platform\PlanEntitlementService;
use App\Support\PlanFeatureCatalog;

/**
 * Business tools for the DressnMore sales agent.
 * Prices/features always come from DressnMoreSalesContext + PlanFeatureCatalog.
 */
final class DressnMoreSalesTools
{
    public function __construct(
        private readonly DressnMoreSalesContext $context,
        private readonly DressnMorePlanAdvisor $advisor,
        private readonly PlanEntitlementService $entitlements,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function getPlans(): array
    {
        return $this->context->plans();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPlanDetails(string $slug): ?array
    {
        $normalized = PlanFeatureCatalog::normalizePlanSlug($slug);
        foreach ($this->context->plans() as $plan) {
            if (($plan['slug'] ?? '') === $normalized) {
                return $plan;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFeatureAvailability(string $featureKey, ?string $planSlug = null): array
    {
        $definition = PlanFeatureCatalog::definition($featureKey);
        if ($definition === null) {
            return [
                'feature_key' => $featureKey,
                'label' => $featureKey,
                'unknown' => true,
                'available' => false,
                'minimum_plan' => null,
                'reason' => 'This capability is not listed in PlanFeatureCatalog.',
                'source' => 'PlanFeatureCatalog',
            ];
        }
        $minimum = $this->entitlements->getRequiredPlan($featureKey);
        $plans = [];
        foreach ($this->context->plans() as $plan) {
            $slug = (string) ($plan['slug'] ?? '');
            $feature = collect($plan['features'] ?? [])->firstWhere('key', $featureKey);
            $plans[$slug] = [
                'included' => (bool) ($feature['included'] ?? false),
                'value' => $feature['value'] ?? null,
            ];
        }

        $onPlan = $planSlug === null ? null : ($plans[PlanFeatureCatalog::normalizePlanSlug($planSlug)] ?? null);

        return [
            'feature_key' => $featureKey,
            'label' => $definition['label'] ?? $featureKey,
            'minimum_plan' => $minimum,
            'unknown' => false,
            'available' => $onPlan !== null ? (bool) ($onPlan['included'] ?? false) : null,
            'required_plan' => $minimum,
            'reason' => $onPlan !== null && ! ($onPlan['included'] ?? false)
                ? PlanFeatureCatalog::upgradeMessageFor($featureKey)
                : null,
            'on_requested_plan' => $onPlan,
            'by_plan' => $plans,
            'source' => 'PlanFeatureCatalog',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getUpgradePath(string $featureKey, ?string $currentPlan = null): array
    {
        $minimum = $this->entitlements->getRequiredPlan($featureKey);
        $path = [];
        foreach (PlanFeatureCatalog::publicPlanSlugs() as $slug) {
            if (PlanFeatureCatalog::planRankOf($slug) >= PlanFeatureCatalog::planRankOf($minimum)) {
                $path[] = $this->getPlanDetails($slug);
            }
        }
        $required = $this->getPlanDetails($minimum);
        $current = $currentPlan ? $this->getPlanDetails($currentPlan) : null;
        $delta = null;
        if ($required !== null && $current !== null && $required['price'] !== null && $current['price'] !== null) {
            $delta = (float) $required['price'] - (float) $current['price'];
        }

        return [
            'feature_key' => $featureKey,
            'current_plan' => $currentPlan,
            'required_plan' => $minimum,
            'upgrade_path' => array_values(array_filter($path)),
            'price_difference' => $delta,
            'source' => 'PlanFeatureCatalog',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function comparePlans(string $planA, string $planB): array
    {
        $a = $this->getPlanDetails($planA);
        $b = $this->getPlanDetails($planB);
        $features = [];
        foreach (PlanFeatureCatalog::definitions() as $def) {
            $key = $def['key'];
            $fa = collect($a['features'] ?? [])->firstWhere('key', $key);
            $fb = collect($b['features'] ?? [])->firstWhere('key', $key);
            $features[] = [
                'key' => $key,
                'label' => $def['label'],
                $a['slug'] ?? $planA => $fa['included'] ?? $fa['value'] ?? null,
                $b['slug'] ?? $planB => $fb['included'] ?? $fb['value'] ?? null,
            ];
        }

        return [
            'plan_a' => $a,
            'plan_b' => $b,
            'price_a' => $a['price'] ?? null,
            'price_b' => $b['price'] ?? null,
            'features' => $features,
            'source' => 'subscription_system',
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    public function recommendPlan(array $profile): array
    {
        return $this->advisor->recommend($profile);
    }
}

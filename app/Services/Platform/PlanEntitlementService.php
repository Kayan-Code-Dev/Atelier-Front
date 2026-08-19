<?php

namespace App\Services\Platform;

use App\Models\Central\Tenant;
use App\Support\PlanFeatureCatalog;
use App\Support\PlanFeatureGate;

/**
 * Central entitlement queries for Product-Led Growth gating.
 */
class PlanEntitlementService
{
    public function __construct(
        private readonly PlanFeatureGate $gate,
    ) {}

    public function getCurrentPlan(Tenant $tenant): string
    {
        if ($tenant->isDemo() && $tenant->status === 'active') {
            return 'demo';
        }

        if ($tenant->isOnCustomPlan()) {
            return 'custom';
        }

        $tenant->loadMissing('plan');

        return PlanFeatureCatalog::normalizePlanSlug($tenant->plan?->slug);
    }

    public function canAccess(Tenant $tenant, string $featureKey): bool
    {
        if (! PlanFeatureCatalog::isBooleanKey($featureKey)) {
            return true;
        }

        return $this->gate->isEnabled($tenant, $featureKey);
    }

    public function getRequiredPlan(string $featureKey): string
    {
        return PlanFeatureCatalog::minimumPlanFor($featureKey);
    }

    public function getLimit(Tenant $tenant, string $metricKey): int
    {
        return $this->gate->limit($tenant, $metricKey);
    }

    public function getUsage(Tenant $tenant, string $metricKey): int
    {
        return app(\App\Services\Tenant\TenantQuotaService::class)
            ->usageForMetric($tenant, $metricKey);
    }

    public function isLimitReached(Tenant $tenant, string $metricKey): bool
    {
        $limit = $this->getLimit($tenant, $metricKey);
        if ($limit <= 0) {
            return false;
        }

        return $this->getUsage($tenant, $metricKey) >= $limit;
    }

    /**
     * Lowest plan that unlocks the feature (never pushes unnecessarily high).
     */
    public function getUpgradeRecommendation(Tenant $tenant, string $featureKey): string
    {
        $required = $this->getRequiredPlan($featureKey);
        $current = $this->getCurrentPlan($tenant);

        if (PlanFeatureCatalog::planRankOf($current) >= PlanFeatureCatalog::planRankOf($required)) {
            return $required;
        }

        return $required;
    }

    /**
     * Structured payload for upgrade UX / API 403 bodies.
     *
     * @return array<string, mixed>
     */
    public function denyPayload(Tenant $tenant, string $featureKey): array
    {
        $definition = PlanFeatureCatalog::definition($featureKey) ?? [];
        $required = $this->getRequiredPlan($featureKey);
        $current = $this->getCurrentPlan($tenant);

        return [
            'code' => 'plan_feature_required',
            'feature_key' => $featureKey,
            'feature_name' => $definition['label'] ?? PlanFeatureCatalog::labelFor($featureKey) ?? $featureKey,
            'description' => $definition['description'] ?? '',
            'upgrade_message' => PlanFeatureCatalog::upgradeMessageFor($featureKey),
            'current_plan' => $current,
            'required_plan' => $required,
            'recommended_plan' => $this->getUpgradeRecommendation($tenant, $featureKey),
            'category' => $definition['category'] ?? null,
        ];
    }

    /**
     * Comparison matrix for pricing page (generated from catalog + plan features).
     *
     * @return array<string, mixed>
     */
    public function comparisonMatrix(): array
    {
        $matrix = PlanFeatureCatalog::defaultMatrix();
        $plans = [];

        foreach (PlanFeatureCatalog::publicPlanSlugs() as $slug) {
            $plans[] = [
                'code' => $slug,
                'features' => $matrix[$slug] ?? [],
            ];
        }

        $rows = [];
        foreach (PlanFeatureCatalog::definitions() as $definition) {
            if (($definition['feature_type'] ?? '') === 'limit') {
                $cells = [];
                foreach (PlanFeatureCatalog::publicPlanSlugs() as $slug) {
                    $value = (int) ($matrix[$slug][$definition['key']] ?? 0);
                    $cells[$slug] = [
                        'type' => 'limit',
                        'value' => $value,
                        'label' => PlanFeatureCatalog::formatLimitLabel($definition['key'], $value)
                            ?? ($value === 0 ? 'غير محدود' : (string) $value),
                        'unlimited' => $value === 0,
                        'custom' => $slug === PlanFeatureCatalog::PLAN_BUSINESS && $value === 0,
                    ];
                }
            } else {
                $cells = [];
                foreach (PlanFeatureCatalog::publicPlanSlugs() as $slug) {
                    $enabled = (bool) ($matrix[$slug][$definition['key']] ?? false);
                    $cells[$slug] = [
                        'type' => 'boolean',
                        'included' => $enabled,
                        'label' => $enabled ? 'متضمن' : 'يتطلب ترقية',
                    ];
                }
            }

            $rows[] = [
                'feature_key' => $definition['key'],
                'name' => $definition['label'],
                'description' => $definition['description'],
                'category' => $definition['category'],
                'minimum_plan' => $definition['minimum_plan'],
                'feature_type' => $definition['feature_type'],
                'cells' => $cells,
            ];
        }

        return [
            'plans' => $plans,
            'rows' => $rows,
        ];
    }
}

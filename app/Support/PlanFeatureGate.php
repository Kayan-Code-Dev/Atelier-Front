<?php

namespace App\Support;

use App\Models\Central\Tenant;
use Illuminate\Validation\ValidationException;

class PlanFeatureGate
{
    public function isEnabled(Tenant $tenant, string $featureKey): bool
    {
        // Demo tenants (no commercial plan) get full product access until expiry.
        if ($tenant->isDemo() && $tenant->status === 'active') {
            return true;
        }

        $customValue = $this->customEntitlementValue($tenant, $featureKey);
        if ($customValue !== false) {
            return PlanFeatureCatalog::isEnabledValue($customValue);
        }

        $tenant->loadMissing(['plan.features']);

        $plan = $tenant->plan;

        if ($plan === null) {
            return app()->environment('testing');
        }

        $feature = $plan->features->firstWhere('feature_key', $featureKey);

        if ($feature === null) {
            return false;
        }

        return PlanFeatureCatalog::isEnabledValue($feature->feature_value);
    }

    public function isAnyEnabled(Tenant $tenant, string ...$featureKeys): bool
    {
        foreach ($featureKeys as $featureKey) {
            if ($this->isEnabled($tenant, $featureKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Numeric plan limit. 0 means unlimited.
     */
    public function limit(Tenant $tenant, string $featureKey): int
    {
        if ($tenant->isDemo() && $tenant->status === 'active') {
            return 0;
        }

        $customValue = $this->customEntitlementValue($tenant, $featureKey);
        if ($customValue !== false) {
            return max(0, (int) $customValue);
        }

        $tenant->loadMissing(['plan.features']);

        $plan = $tenant->plan;
        if ($plan === null) {
            return app()->environment('testing') ? 0 : 0;
        }

        $feature = $plan->features->firstWhere('feature_key', $featureKey);
        if ($feature === null) {
            // Missing limit key = unlimited for backwards compatibility with old plans.
            return 0;
        }

        return max(0, (int) $feature->feature_value);
    }

    /**
     * @throws ValidationException
     */
    public function assertUnderLimit(Tenant $tenant, string $featureKey, int $currentCount, string $message): void
    {
        $max = $this->limit($tenant, $featureKey);
        if ($max <= 0) {
            return;
        }

        if ($currentCount >= $max) {
            $required = PlanFeatureCatalog::minimumPlanFor($featureKey);
            // Limits themselves recommend next commercial tier (legacy: pro / enterprise).
            $recommended = match ($featureKey) {
                'branches.max', 'users.max', 'invoices.monthly.max', 'invoices.sale.max', 'invoices.rent.max', 'invoices.tailoring.max' => 'pro',
                'ai_assistant.chat_monthly.max' => 'enterprise',
                'smart_assistant.messages_monthly.max' => 'pro',
                default => $required === PlanFeatureCatalog::PLAN_STARTER ? 'pro' : ($required === PlanFeatureCatalog::PLAN_PROFESSIONAL ? 'pro' : 'enterprise'),
            };

            throw ValidationException::withMessages([
                'plan_limit' => [$message],
                'code' => ['plan_limit_reached'],
                'feature_key' => [$featureKey],
                'used' => [(string) $currentCount],
                'limit' => [(string) $max],
                'recommended_plan' => [$recommended],
            ]);
        }
    }

    /**
     * Custom subscription entitlements are the source of truth when present.
     *
     * @return string|false  false means "no custom subscription"; string is the stored value (may be empty)
     */
    private function customEntitlementValue(Tenant $tenant, string $featureKey): string|false
    {
        $subscription = $tenant->currentCustomSubscription();
        if ($subscription === null || ! is_array($subscription->entitlements)) {
            return false;
        }

        if (! array_key_exists($featureKey, $subscription->entitlements)) {
            return PlanFeatureCatalog::isIntegerKey($featureKey) ? '0' : 'false';
        }

        return (string) $subscription->entitlements[$featureKey];
    }
}

<?php

namespace App\Support;

use App\Models\Central\Plan;
use App\Models\Central\Subscription;
use App\Models\Central\Tenant;
use App\Services\Platform\CustomTenantSubscriptionService;
use Carbon\CarbonImmutable;

class TenantSubscriptionPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function forTenant(Tenant $tenant): array
    {
        $tenant->loadMissing(['plan.features', 'customSubscription']);

        $custom = $tenant->currentCustomSubscription();
        if ($custom instanceof Subscription) {
            return $this->forCustomSubscription($tenant, $custom);
        }

        /** @var Plan|null $plan */
        $plan = $tenant->plan;
        $isDemo = $tenant->isDemo();
        $startsAt = $tenant->subscription_starts_at !== null
            ? CarbonImmutable::parse((string) $tenant->subscription_starts_at)
            : CarbonImmutable::now();
        $expiresAt = $tenant->subscription_ends_at !== null
            ? CarbonImmutable::parse((string) $tenant->subscription_ends_at)
            : null;

        $normalizedSlug = $isDemo
            ? 'demo'
            : PlanFeatureCatalog::normalizePlanSlug($plan?->slug);
        $isCustomPricing = $normalizedSlug === PlanFeatureCatalog::PLAN_BUSINESS
            || ($plan !== null && (float) $plan->price <= 0 && $normalizedSlug === PlanFeatureCatalog::PLAN_BUSINESS);
        $isPaid = ! $isDemo && $plan !== null && (float) $plan->price > 0;
        $daysRemaining = null;
        if ($expiresAt !== null) {
            $daysRemaining = max(
                0,
                CarbonImmutable::today()->startOfDay()->diffInDays($expiresAt->startOfDay(), false)
            );
        }

        $lifecycleStatus = 'active';
        if (isset($tenant->cancelled_at) && $tenant->cancelled_at !== null) {
            $lifecycleStatus = 'cancelled';
        } elseif ($expiresAt !== null && $expiresAt->lt(CarbonImmutable::today())) {
            $lifecycleStatus = 'expired';
        }

        $features = [];
        $enabledModules = [];
        $lockedModules = [];

        if ($isDemo && $tenant->status === 'active') {
            foreach (PlanFeatureCatalog::definitions() as $definition) {
                $key = $definition['key'];
                if (PlanFeatureCatalog::isBooleanKey($key)) {
                    $features[$key] = 'true';
                    if (str_ends_with($key, '.enabled')) {
                        $enabledModules[] = PlanFeatureCatalog::moduleKeyFromFeature($key);
                    }
                } else {
                    $features[$key] = '0';
                }
            }
        } else {
            $featureMap = [];
            foreach ($plan?->features ?? [] as $feature) {
                $featureMap[$feature->feature_key] = (string) $feature->feature_value;
                $features[$feature->feature_key] = (string) $feature->feature_value;
            }

            foreach (PlanFeatureCatalog::definitions() as $definition) {
                $key = $definition['key'];
                if (! PlanFeatureCatalog::isBooleanKey($key)) {
                    continue;
                }

                $enabled = PlanFeatureCatalog::isEnabledValue($featureMap[$key] ?? null);
                if (! str_ends_with($key, '.enabled')) {
                    continue;
                }

                $module = PlanFeatureCatalog::moduleKeyFromFeature($key);
                if ($enabled) {
                    $enabledModules[] = $module;
                } else {
                    $lockedModules[] = [
                        'module' => $module,
                        'feature_key' => $key,
                        'name' => $definition['label'],
                        'description' => $definition['description'],
                        'minimum_plan' => $definition['minimum_plan'],
                        'upgrade_message' => $definition['upgrade_message'],
                    ];
                }
            }
        }

        $currency = PlanCurrency::normalize($plan?->currency ?? 'EGP');
        $displayName = $isDemo
            ? 'حساب تجريبي'
            : ($plan?->name ?? match ($normalizedSlug) {
                PlanFeatureCatalog::PLAN_FREE, 'basic' => 'أساسية',
                PlanFeatureCatalog::PLAN_STARTER => 'البداية',
                PlanFeatureCatalog::PLAN_PROFESSIONAL, 'pro' => 'احترافية',
                PlanFeatureCatalog::PLAN_BUSINESS, 'enterprise' => 'مؤسسات',
                default => 'أساسية',
            });

        return [
            'account_type' => $isDemo ? 'demo' : ($isPaid || $isCustomPricing ? 'paid' : 'free'),
            'lifecycle_status' => $lifecycleStatus,
            'plan_id' => $plan?->id,
            'plan_code' => $plan?->slug ?? ($normalizedSlug === '' ? 'basic' : $normalizedSlug),
            'plan_name' => $displayName,
            'price' => $isDemo || $isCustomPricing ? 0.0 : (float) ($plan?->price ?? 0),
            'is_custom_pricing' => $isCustomPricing,
            'price_label' => $isCustomPricing ? 'تسعير مخصص' : null,
            'currency' => $currency,
            'currency_symbol' => PlanCurrency::symbol($currency),
            'billing_cycle' => $plan?->billing_cycle ?? 'monthly',
            'starts_at' => $startsAt->toDateString(),
            'expires_at' => $expiresAt?->toDateString(),
            'can_renew' => $isDemo ? false : ($isPaid ? $lifecycleStatus !== 'cancelled' : true),
            'can_cancel' => ! $isDemo && ($lifecycleStatus === 'active' || $lifecycleStatus === 'expired'),
            'days_remaining' => $daysRemaining,
            'cancelled_at' => $tenant->cancelled_at?->toDateString() ?? null,
            'cancellation_reason' => $tenant->cancellation_reason ?? null,
            'features' => $features,
            'enabled_modules' => array_values(array_unique($enabledModules)),
            'locked_modules' => $lockedModules,
            'is_demo' => $isDemo,
            'is_custom' => false,
            'price_monthly' => $plan !== null ? (float) ($plan->monthly_price ?? $plan->price ?? 0) : 0.0,
            'price_yearly' => $plan !== null ? (float) ($plan->yearly_price ?? 0) : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function forCustomSubscription(Tenant $tenant, Subscription $subscription): array
    {
        $startsAt = $subscription->starts_at !== null
            ? CarbonImmutable::parse((string) $subscription->starts_at)
            : ($tenant->subscription_starts_at !== null
                ? CarbonImmutable::parse((string) $tenant->subscription_starts_at)
                : CarbonImmutable::now());
        $expiresAt = $subscription->ends_at !== null
            ? CarbonImmutable::parse((string) $subscription->ends_at)
            : ($tenant->subscription_ends_at !== null
                ? CarbonImmutable::parse((string) $tenant->subscription_ends_at)
                : null);

        $daysRemaining = null;
        if ($expiresAt !== null) {
            $daysRemaining = max(
                0,
                CarbonImmutable::today()->startOfDay()->diffInDays($expiresAt->startOfDay(), false)
            );
        }

        $lifecycleStatus = 'active';
        if (isset($tenant->cancelled_at) && $tenant->cancelled_at !== null) {
            $lifecycleStatus = 'cancelled';
        } elseif ($subscription->status === 'cancelled') {
            $lifecycleStatus = 'cancelled';
        } elseif ($expiresAt !== null && $expiresAt->lt(CarbonImmutable::today())) {
            $lifecycleStatus = 'expired';
        }

        $entitlements = is_array($subscription->entitlements) ? $subscription->entitlements : [];
        $features = [];
        $enabledModules = [];
        $lockedModules = [];
        $enabledFeatures = [];

        foreach (PlanFeatureCatalog::definitions() as $definition) {
            $key = $definition['key'];
            $raw = $entitlements[$key] ?? (PlanFeatureCatalog::isIntegerKey($key) ? '0' : 'false');
            $features[$key] = (string) $raw;

            if (PlanFeatureCatalog::isBooleanKey($key) && PlanFeatureCatalog::isEnabledValue((string) $raw)) {
                $enabledFeatures[] = [
                    'key' => $key,
                    'label' => $definition['label'],
                ];
            }

            if (! PlanFeatureCatalog::isBooleanKey($key) || ! str_ends_with($key, '.enabled')) {
                continue;
            }

            $module = PlanFeatureCatalog::moduleKeyFromFeature($key);
            if (PlanFeatureCatalog::isEnabledValue((string) $raw)) {
                $enabledModules[] = $module;
            } else {
                $lockedModules[] = [
                    'module' => $module,
                    'feature_key' => $key,
                    'name' => $definition['label'],
                    'description' => $definition['description'],
                    'minimum_plan' => 'custom',
                    'upgrade_message' => $definition['upgrade_message'],
                ];
            }
        }

        $interval = strtolower((string) ($subscription->billing_interval ?: 'monthly')) === 'yearly'
            ? 'yearly'
            : 'monthly';
        $customService = app(CustomTenantSubscriptionService::class);
        $price = $customService->activePrice($subscription);
        $currency = PlanCurrency::normalize($subscription->currency ?? 'EGP');
        $isPaid = $price > 0;

        return [
            'account_type' => $isPaid ? 'paid' : 'free',
            'lifecycle_status' => $lifecycleStatus,
            'plan_id' => null,
            'plan_code' => 'custom',
            'plan_name' => 'Custom Plan',
            'price' => $price,
            'is_custom_pricing' => false,
            'price_label' => null,
            'currency' => $currency,
            'currency_symbol' => PlanCurrency::symbol($currency),
            'billing_cycle' => $interval,
            'starts_at' => $startsAt->toDateString(),
            'expires_at' => $expiresAt?->toDateString(),
            'can_renew' => false,
            'can_cancel' => $lifecycleStatus === 'active' || $lifecycleStatus === 'expired',
            'days_remaining' => $daysRemaining,
            'cancelled_at' => $tenant->cancelled_at?->toDateString() ?? null,
            'cancellation_reason' => $tenant->cancellation_reason ?? null,
            'features' => $features,
            'enabled_features' => $enabledFeatures,
            'enabled_modules' => array_values(array_unique($enabledModules)),
            'locked_modules' => $lockedModules,
            'is_demo' => false,
            'is_custom' => true,
            'price_monthly' => (float) ($subscription->price_monthly ?? 0),
            'price_yearly' => (float) ($subscription->price_yearly ?? 0),
            'usage_limits' => $this->usageLimitsFromFeatures($features),
        ];
    }

    /**
     * @param  array<string, string>  $features
     * @return list<array<string, mixed>>
     */
    private function usageLimitsFromFeatures(array $features): array
    {
        $limits = [];

        foreach (PlanFeatureCatalog::definitions() as $definition) {
            if (($definition['type'] ?? '') !== 'integer') {
                continue;
            }

            $key = $definition['key'];
            $value = max(0, (int) ($features[$key] ?? 0));
            $limits[] = [
                'key' => $key,
                'label' => $definition['label'],
                'limit' => $value,
                'unlimited' => $value === 0,
            ];
        }

        return $limits;
    }
}

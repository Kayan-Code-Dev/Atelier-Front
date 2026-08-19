<?php

namespace App\Services\Platform;

use App\Models\Central\Payment;
use App\Models\Central\PaymentGateway;
use App\Models\Central\Plan;
use App\Models\Central\Tenant;
use App\Services\Platform\TenantProvisioningService;
use App\Support\PlanFeatureCatalog;
use App\Support\PlanCurrency;
use App\Support\TenantSubscriptionPresenter;
use App\Services\Platform\PlanEntitlementService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use RuntimeException;

class TenantSubscriptionBillingService
{
    public function __construct(
        private readonly TenantProvisioningService $tenantProvisioningService,
        private readonly TenantSubscriptionPresenter $subscriptionPresenter,
        private readonly TenantPlanChangeRequestService $changeRequestService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(Tenant $tenant): array
    {
        $tenant->loadMissing(['plan.features', 'customSubscription']);

        return [
            'subscription' => $this->subscriptionPresenter->forTenant($tenant),
            'tenant' => [
                'id' => (string) $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'available_plans' => $this->availablePlans($tenant),
            'feature_catalog' => PlanFeatureCatalog::definitions(),
            'comparison' => app(PlanEntitlementService::class)->comparisonMatrix(),
            'pending_change_request' => $this->changeRequestService->pendingForTenant($tenant),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activePaymentGateways(): array
    {
        return PaymentGateway::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->map(fn (PaymentGateway $gateway): array => $this->presentGateway($gateway))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function renew(Tenant $tenant, array $data): array
    {
        $custom = $tenant->currentCustomSubscription();
        if ($custom !== null) {
            throw new RuntimeException('تجديد الباقة المخصصة يتم من لوحة الإدارة فقط.');
        }

        $plan = $tenant->plan;
        if ($plan === null) {
            throw new RuntimeException('Tenant has no active plan');
        }

        if ((float) $plan->price > 0) {
            throw new RuntimeException('Paid plans require upgrade flow');
        }

        $days = (int) ($data['extension_days'] ?? $plan->duration_days ?? 30);
        $tenant = $this->tenantProvisioningService->renew($tenant, ['days' => $days]);

        return $this->subscriptionPresenter->forTenant($tenant->refresh()->load(['plan.features']));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function upgrade(Tenant $tenant, array $data): array
    {
        $plan = Plan::query()
            ->with('features')
            ->where('slug', (string) ($data['plan_code'] ?? ''))
            ->where('status', 'active')
            ->firstOrFail();

        $isPaid = (float) $plan->price > 0;

        if ($isPaid) {
            if (! config('subscription.allow_mock_payments')) {
                throw new RuntimeException(
                    'يرجى اختيار الباقة وإتمام الدفع وإرفاق إثبات التحويل لمراجعة الإدارة'
                );
            }

            $this->assertMockPaymentConfirmed($data);
            $gateway = PaymentGateway::query()
                ->where('id', (int) $data['payment_gateway_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $payment = Payment::query()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'payment_gateway_id' => $gateway->id,
                'purpose' => 'subscription_upgrade',
                'amount' => $plan->price,
                'currency' => PlanCurrency::normalize($plan->currency ?? 'EGP'),
                'method' => $gateway->type,
                'reference' => 'TEST-'.Str::upper(Str::random(10)),
                'status' => 'paid',
                'paid_at' => CarbonImmutable::now(),
                'notes' => 'Test-only mock payment (SUBSCRIPTION_ALLOW_MOCK_PAYMENTS=true)',
            ]);

            $gateway->increment('usage_count');
        }

        if ($tenant->isOnCustomPlan()) {
            app(CustomTenantSubscriptionService::class)->clear($tenant);
        }

        $tenant->plan_id = $plan->id;
        $tenant->subscription_starts_at = CarbonImmutable::now();
        $tenant->subscription_ends_at = CarbonImmutable::now()->addDays((int) ($plan->duration_days ?? 30));
        $tenant->status = 'active';
        $tenant->save();

        app(SubscriptionAdminService::class)->ensureForTenant($tenant);

        return $this->subscriptionPresenter->forTenant($tenant->refresh()->load(['plan.features', 'customSubscription']));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function availablePlans(Tenant $tenant): array
    {
        return Plan::query()
            ->with('features')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Plan $plan): array => $this->presentPlanOption($plan, $tenant))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentPlanOption(Plan $plan, Tenant $tenant): array
    {
        $slug = (string) $plan->slug;
        $normalized = PlanFeatureCatalog::normalizePlanSlug($slug);
        $isCustom = in_array($normalized, [PlanFeatureCatalog::PLAN_BUSINESS], true)
            && (float) $plan->price <= 0;
        $isPaid = (float) $plan->price > 0;
        $currency = PlanCurrency::normalize($plan->currency ?? 'EGP');
        $currentSlug = (string) ($tenant->plan?->slug ?? '');
        $onCustom = $tenant->isOnCustomPlan();
        $isCurrent = ! $onCustom && ($currentSlug === $slug || ((int) $tenant->plan_id === (int) $plan->id && $tenant->plan_id !== null));

        return [
            'code' => $slug,
            'name' => $plan->name,
            'account_type' => ($isPaid || $isCustom) ? 'paid' : 'free',
            'price' => $isCustom ? 0.0 : (float) $plan->price,
            'is_custom_pricing' => $isCustom,
            'price_label' => $isCustom ? 'تسعير مخصص' : null,
            'currency' => $currency,
            'currency_symbol' => PlanCurrency::symbol($currency),
            'billing_cycle' => $plan->billing_cycle,
            'billing_period_days' => $plan->duration_days,
            'description' => $plan->description ?? '',
            'features' => $this->planFeatureLabels($plan),
            'is_current' => $isCurrent,
            'recommended' => $slug === 'pro',
            'action' => $isCurrent
                ? 'current'
                : (PlanFeatureCatalog::planRankOf($normalized) > PlanFeatureCatalog::planRankOf($currentSlug) ? 'upgrade' : 'select'),
        ];
    }

    /**
     * @return list<string>
     */
    private function planFeatureLabels(Plan $plan): array
    {
        $labels = [];

        foreach ($plan->features as $feature) {
            $key = (string) $feature->feature_key;
            $value = $feature->feature_value;

            if (str_ends_with($key, '.enabled')) {
                if (! PlanFeatureCatalog::isEnabledValue($value)) {
                    continue;
                }
                $label = PlanFeatureCatalog::labelFor($key);
                if ($label !== null) {
                    $labels[] = $label;
                }
                continue;
            }

            if (PlanFeatureCatalog::isIntegerKey($key)) {
                // Skip unlimited (0) — avoids noisy "بلا حد" lines on every plan card.
                if ((int) $value <= 0) {
                    continue;
                }
                $formatted = PlanFeatureCatalog::formatLimitLabel($key, $value);
                if ($formatted !== null) {
                    $labels[] = $formatted;
                }
            }
        }

        return array_values(array_unique($labels));
    }

    /**
     * @return array<string, mixed>
     */
    private function presentGateway(PaymentGateway $gateway): array
    {
        return [
            'id' => (string) $gateway->id,
            'name' => $gateway->name,
            'type' => $gateway->type,
            'account_holder' => $gateway->account_holder,
            'account_number' => $gateway->account_number,
            'bank_name' => $gateway->bank_name,
            'iban' => $gateway->iban,
            'instructions' => $gateway->instructions,
            'is_active' => $gateway->is_active,
            'display_order' => $gateway->display_order,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertMockPaymentConfirmed(array $data): void
    {
        if (! filter_var($data['mock_payment_confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            throw new RuntimeException('Payment confirmation is required');
        }

        if (empty($data['payment_gateway_id'])) {
            throw new RuntimeException('Payment gateway is required');
        }
    }
}

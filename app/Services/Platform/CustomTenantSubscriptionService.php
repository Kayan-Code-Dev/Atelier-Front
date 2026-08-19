<?php

namespace App\Services\Platform;

use App\Models\Central\Subscription;
use App\Models\Central\Tenant;
use App\Support\PlanCurrency;
use App\Support\PlanFeatureCatalog;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Tenant-specific custom subscriptions live on `subscriptions` (is_custom=true)
 * and never create or mutate public Plan Catalog rows.
 */
class CustomTenantSubscriptionService
{
    /**
     * @param  array{
     *   monthly_price?: mixed,
     *   yearly_price?: mixed,
     *   billing_interval?: mixed,
     *   starts_at?: mixed,
     *   ends_at?: mixed,
     *   currency?: mixed,
     *   features?: mixed
     * }  $data
     */
    public function assign(Tenant $tenant, array $data): Subscription
    {
        $interval = $this->normalizeInterval($data['billing_interval'] ?? 'monthly');
        $startsAt = isset($data['starts_at']) && trim((string) $data['starts_at']) !== ''
            ? CarbonImmutable::parse((string) $data['starts_at'])
            : CarbonImmutable::now();
        $endsAt = isset($data['ends_at']) && trim((string) $data['ends_at']) !== ''
            ? CarbonImmutable::parse((string) $data['ends_at'])
            : $this->endsAtFrom($startsAt, $interval);

        $payload = [
            'tenant_id' => $tenant->id,
            'plan_id' => null,
            'is_custom' => true,
            'billing_interval' => $interval,
            'price_monthly' => $this->normalizePrice($data['monthly_price'] ?? 0),
            'price_yearly' => $this->normalizePrice($data['yearly_price'] ?? 0),
            'currency' => PlanCurrency::normalize($data['currency'] ?? 'EGP'),
            'entitlements' => $this->normalizeEntitlements(is_array($data['features'] ?? null) ? $data['features'] : []),
            'status' => $endsAt->lt(CarbonImmutable::now()) ? 'expired' : 'active',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ];

        Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_custom', false)
            ->whereIn('status', ['active', 'pending'])
            ->update(['status' => 'cancelled']);

        $existing = Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_custom', true)
            ->orderByDesc('id')
            ->first();

        if ($existing instanceof Subscription) {
            $existing->fill($payload);
            $existing->save();
            $subscription = $existing;
        } else {
            $subscription = Subscription::query()->create($payload);
        }

        $tenant->plan_id = null;
        $tenant->subscription_starts_at = $startsAt;
        $tenant->subscription_ends_at = $endsAt;
        if ($tenant->status === 'expired' && $subscription->status === 'active') {
            $tenant->status = 'active';
        }
        $tenant->save();

        return $subscription->refresh();
    }

    public function clear(Tenant $tenant): void
    {
        Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_custom', true)
            ->whereIn('status', ['active', 'pending'])
            ->update(['status' => 'cancelled']);
    }

    public function forTenant(Tenant $tenant): ?Subscription
    {
        return $tenant->currentCustomSubscription();
    }

    public function extendByInterval(Subscription $subscription, ?CarbonImmutable $from = null): CarbonImmutable
    {
        if (! $subscription->is_custom) {
            throw new InvalidArgumentException('Subscription is not custom.');
        }

        $now = CarbonImmutable::now();
        $base = $from ?? ($subscription->ends_at !== null
            ? CarbonImmutable::parse((string) $subscription->ends_at)
            : $now);
        if ($base->lt($now)) {
            $base = $now;
        }

        $interval = $this->normalizeInterval($subscription->billing_interval);

        return $this->endsAtFrom($base, $interval);
    }

    public function endsAtFrom(CarbonImmutable $startsAt, string $interval): CarbonImmutable
    {
        return $this->normalizeInterval($interval) === 'yearly'
            ? $startsAt->addYear()
            : $startsAt->addMonth();
    }

    public function activePrice(Subscription $subscription): float
    {
        $interval = $this->normalizeInterval($subscription->billing_interval);

        return $interval === 'yearly'
            ? (float) ($subscription->price_yearly ?? 0)
            : (float) ($subscription->price_monthly ?? 0);
    }

    /**
     * @param  array<string, mixed>  $features
     * @return array<string, string>
     */
    public function normalizeEntitlements(array $features): array
    {
        $out = [];

        foreach (PlanFeatureCatalog::definitions() as $definition) {
            $key = $definition['key'];
            if (array_key_exists($key, $features)) {
                $out[$key] = PlanFeatureCatalog::normalizeValue($key, $features[$key]);
            } elseif (($definition['type'] ?? '') === 'integer') {
                $out[$key] = '0';
            } else {
                $out[$key] = 'false';
            }
        }

        return $out;
    }

    public function isCustomPayload(array $data): bool
    {
        return array_key_exists('custom_plan', $data)
            && filter_var($data['custom_plan'], FILTER_VALIDATE_BOOLEAN);
    }

    private function normalizeInterval(mixed $interval): string
    {
        return strtolower(trim((string) $interval)) === 'yearly' ? 'yearly' : 'monthly';
    }

    private function normalizePrice(mixed $value): string
    {
        return number_format(max(0, (float) $value), 2, '.', '');
    }
}

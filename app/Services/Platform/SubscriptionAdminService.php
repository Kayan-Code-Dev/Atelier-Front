<?php

namespace App\Services\Platform;

use App\Models\Central\Plan;
use App\Models\Central\Subscription;
use App\Models\Central\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class SubscriptionAdminService
{
    /**
     * Ensure every non-demo tenant with a plan has a subscription row.
     */
    public function syncMissingFromTenants(): int
    {
        $created = 0;

        Tenant::query()
            ->whereNotNull('plan_id')
            ->orderBy('id')
            ->chunkById(100, function ($tenants) use (&$created): void {
                foreach ($tenants as $tenant) {
                    if ($tenant->isDemo()) {
                        continue;
                    }
                    $before = Subscription::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('plan_id', $tenant->plan_id)
                        ->exists();
                    $this->ensureForTenant($tenant);
                    if (! $before) {
                        $created++;
                    }
                }
            });

        return $created;
    }

    /**
     * Create or refresh the current subscription for a tenant with a plan.
     */
    public function ensureForTenant(Tenant $tenant): ?Subscription
    {
        if ($tenant->isDemo()) {
            return null;
        }

        $custom = $tenant->currentCustomSubscription();
        if ($custom instanceof Subscription) {
            return $custom;
        }

        if ($tenant->plan_id === null) {
            return null;
        }

        $subscription = Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->where('plan_id', $tenant->plan_id)
            ->orderByDesc('id')
            ->first();

        $startsAt = $tenant->subscription_starts_at
            ? CarbonImmutable::parse((string) $tenant->subscription_starts_at)
            : CarbonImmutable::now();
        $endsAt = $tenant->subscription_ends_at
            ? CarbonImmutable::parse((string) $tenant->subscription_ends_at)
            : $startsAt->addDays($this->planDurationDays((int) $tenant->plan_id));

        $status = $this->statusFromDates($endsAt, (string) ($tenant->status ?? 'active'));

        if ($subscription instanceof Subscription) {
            if (in_array($subscription->status, ['active', 'pending'], true)
                && $subscription->ends_at !== null
                && $subscription->ends_at->lt(CarbonImmutable::now())
            ) {
                $subscription->status = 'expired';
                $subscription->save();
            }

            return $subscription->refresh();
        }

        return Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $tenant->plan_id,
            'status' => $status === 'expired' ? 'expired' : 'active',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }

    /**
     * Mark overdue active/pending subscriptions as expired (and sync tenant status).
     *
     * @return array{expired: int}
     */
    public function processExpiry(): array
    {
        $now = CarbonImmutable::now();
        $expired = 0;

        $subscriptions = Subscription::query()
            ->with('tenant')
            ->whereIn('status', ['active', 'pending'])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $now)
            ->orderBy('id')
            ->get();

        foreach ($subscriptions as $subscription) {
            $subscription->status = 'expired';
            $subscription->save();
            $expired++;

            $tenant = $subscription->tenant;
            if ($tenant instanceof Tenant && ! $tenant->isDemo()) {
                $sameCatalogPlan = $subscription->plan_id !== null
                    && (int) $tenant->plan_id === (int) $subscription->plan_id;
                $sameCustom = (bool) $subscription->is_custom && $tenant->plan_id === null;
                if (($sameCatalogPlan || $sameCustom) && $tenant->status === 'active') {
                    $tenant->status = 'expired';
                    $tenant->save();
                }
            }
        }

        return ['expired' => $expired];
    }

    /**
     * @param  array{search?:string|null, status?:string|null}  $filters
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $this->syncMissingSubscriptionsQuietly();
        $this->processExpiry();

        $now = CarbonImmutable::now();
        $query = Subscription::query()
            ->with(['plan', 'tenant'])
            ->whereHas('tenant', function (Builder $builder): void {
                $builder->where(function (Builder $inner): void {
                    $inner->whereNull('metadata->source')
                        ->orWhere('metadata->source', '!=', 'demo');
                });
            })
            ->orderByDesc('id');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $wildcard = '%'.mb_strtolower($search).'%';
            $query->where(function (Builder $builder) use ($wildcard): void {
                $builder->whereRaw('CAST(id AS CHAR) LIKE ?', [$wildcard])
                    ->orWhereHas('tenant', function (Builder $tenantQuery) use ($wildcard): void {
                        $tenantQuery->whereRaw('LOWER(name) LIKE ?', [$wildcard])
                            ->orWhereRaw('LOWER(slug) LIKE ?', [$wildcard]);
                    })
                    ->orWhereHas('plan', function (Builder $planQuery) use ($wildcard): void {
                        $planQuery->whereRaw('LOWER(name) LIKE ?', [$wildcard]);
                    });
            });
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status === 'expired') {
            $query->where(function (Builder $builder) use ($now): void {
                $builder->where('status', 'expired')
                    ->orWhere(function (Builder $inner) use ($now): void {
                        $inner->whereIn('status', ['active', 'pending'])
                            ->whereNotNull('ends_at')
                            ->where('ends_at', '<', $now);
                    });
            });
        } elseif ($status === 'active') {
            $query->where('status', 'active')
                ->where(function (Builder $builder) use ($now): void {
                    $builder->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', $now);
                });
        } elseif ($status !== '') {
            $query->where('status', $status);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array{
     *   plan_id?:int|null,
     *   status?:string|null,
     *   starts_at?:string|null,
     *   ends_at?:string|null
     * }  $data
     */
    public function update(Subscription $subscription, array $data): Subscription
    {
        if (array_key_exists('plan_id', $data) && $data['plan_id'] !== null) {
            $plan = Plan::query()->whereKey((int) $data['plan_id'])->first();
            if (! $plan instanceof Plan) {
                throw new RuntimeException('الباقة غير موجودة.');
            }
            $subscription->plan_id = $plan->id;
        }

        if (array_key_exists('starts_at', $data) && $data['starts_at'] !== null && $data['starts_at'] !== '') {
            $subscription->starts_at = CarbonImmutable::parse((string) $data['starts_at']);
        }

        if (array_key_exists('ends_at', $data) && $data['ends_at'] !== null && $data['ends_at'] !== '') {
            $subscription->ends_at = CarbonImmutable::parse((string) $data['ends_at']);
        }

        if (array_key_exists('status', $data) && $data['status'] !== null && $data['status'] !== '') {
            $subscription->status = (string) $data['status'];
        }

        if ($subscription->ends_at !== null
            && $subscription->ends_at->lt(CarbonImmutable::now())
            && in_array($subscription->status, ['active', 'pending'], true)
        ) {
            $subscription->status = 'expired';
        }

        $subscription->save();
        $this->syncTenantFromSubscription($subscription->fresh(['tenant', 'plan']));

        return $subscription->refresh()->load(['plan', 'tenant']);
    }

    /**
     * @param  array{days?:int|null, months?:int|null}  $data
     */
    public function renew(Subscription $subscription, array $data): Subscription
    {
        $months = isset($data['months']) ? (int) $data['months'] : 0;
        $days = isset($data['days']) ? (int) $data['days'] : 0;

        $now = CarbonImmutable::now();
        $base = $subscription->ends_at !== null
            ? CarbonImmutable::parse((string) $subscription->ends_at)
            : $now;
        if ($base->lt($now)) {
            $base = $now;
        }

        if ($subscription->is_custom && $months <= 0 && $days <= 0) {
            $endsAt = app(CustomTenantSubscriptionService::class)->extendByInterval($subscription, $base);
        } else {
            if ($months <= 0 && $days <= 0) {
                throw new RuntimeException('حدد عدد الأيام أو عدد الشهور للتجديد.');
            }

            $endsAt = $months > 0
                ? $base->addMonths($months)
                : $base->addDays(max(1, $days));
        }

        if ($subscription->starts_at === null) {
            $subscription->starts_at = $now;
        }
        $subscription->ends_at = $endsAt;
        $subscription->status = 'active';
        $subscription->save();

        $this->syncTenantFromSubscription($subscription->fresh(['tenant', 'plan']), forceActive: true);

        return $subscription->refresh()->load(['plan', 'tenant']);
    }

    public function destroy(Subscription $subscription): void
    {
        $tenant = $subscription->tenant;
        $wasCustom = (bool) $subscription->is_custom;
        $planId = $subscription->plan_id !== null ? (int) $subscription->plan_id : null;
        $subscription->delete();

        if ($tenant instanceof Tenant && ($wasCustom ? $tenant->plan_id === null : (int) $tenant->plan_id === (int) $planId)) {
            $other = Subscription::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('status', ['active', 'pending'])
                ->orderByDesc('id')
                ->first();

            if ($other instanceof Subscription) {
                $tenant->plan_id = $other->plan_id;
                $tenant->subscription_starts_at = $other->starts_at;
                $tenant->subscription_ends_at = $other->ends_at;
                $tenant->status = 'active';
            } else {
                $tenant->status = 'expired';
            }
            $tenant->save();
        }
    }

    private function syncMissingSubscriptionsQuietly(): void
    {
        Tenant::query()
            ->whereNotNull('plan_id')
            ->orderBy('id')
            ->chunkById(100, function ($tenants): void {
                foreach ($tenants as $tenant) {
                    if (! $tenant->isDemo()) {
                        $this->ensureForTenant($tenant);
                    }
                }
            });
    }

    private function syncTenantFromSubscription(Subscription $subscription, bool $forceActive = false): void
    {
        $tenant = $subscription->tenant;
        if (! $tenant instanceof Tenant) {
            return;
        }

        $tenant->plan_id = $subscription->plan_id;
        $tenant->subscription_starts_at = $subscription->starts_at;
        $tenant->subscription_ends_at = $subscription->ends_at;

        if ($forceActive || $subscription->status === 'active') {
            $tenant->status = 'active';
        } elseif ($subscription->status === 'expired') {
            $tenant->status = 'expired';
        } elseif ($subscription->status === 'cancelled') {
            $tenant->status = 'suspended';
        }

        $tenant->save();
    }

    private function statusFromDates(CarbonImmutable $endsAt, string $tenantStatus): string
    {
        if ($endsAt->lt(CarbonImmutable::now())) {
            return 'expired';
        }

        return $tenantStatus === 'expired' ? 'expired' : 'active';
    }

    private function planDurationDays(int $planId): int
    {
        $plan = Plan::query()->find($planId);

        return max(1, (int) ($plan?->duration_days ?? 30));
    }
}

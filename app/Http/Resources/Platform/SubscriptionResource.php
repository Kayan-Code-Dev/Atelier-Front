<?php

namespace App\Http\Resources\Platform;

use App\Support\PlanCurrency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $plan = $this->plan;
        $isCustom = (bool) $this->is_custom;
        $currency = PlanCurrency::normalize($isCustom ? ($this->currency ?? 'EGP') : ($plan?->currency ?? 'EGP'));
        $interval = $isCustom
            ? ((string) ($this->billing_interval ?: 'monthly'))
            : ($plan?->billing_cycle);
        $price = $isCustom
            ? ($interval === 'yearly' ? (float) ($this->price_yearly ?? 0) : (float) ($this->price_monthly ?? 0))
            : (float) ($plan?->price ?? 0);

        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant?->id,
                'name' => $this->tenant?->name,
                'slug' => $this->tenant?->slug,
                'status' => $this->tenant?->status,
            ]),
            'plan_id' => $this->plan_id,
            'is_custom' => $isCustom,
            'plan' => $isCustom ? [
                'id' => null,
                'name' => 'Custom Plan',
                'title' => 'Custom Plan',
                'price' => number_format($price, 2, '.', ''),
                'currency' => $currency,
                'currency_symbol' => PlanCurrency::symbol($currency),
                'billing_cycle' => $interval,
                'days' => $this->ends_at && $this->starts_at
                    ? max(1, (int) $this->starts_at->diffInDays($this->ends_at))
                    : ($interval === 'yearly' ? 365 : 30),
            ] : ($plan ? [
                'id' => $plan->id,
                'name' => $plan->name,
                'title' => $plan->name,
                'price' => number_format((float) $plan->price, 2, '.', ''),
                'currency' => $currency,
                'currency_symbol' => PlanCurrency::symbol($currency),
                'billing_cycle' => $plan->billing_cycle,
                'days' => (int) ($plan->duration_days ?? 30),
            ] : null),
            'billing_interval' => $isCustom ? $interval : ($plan?->billing_cycle),
            'price_monthly' => $isCustom ? number_format((float) ($this->price_monthly ?? 0), 2, '.', '') : null,
            'price_yearly' => $isCustom ? number_format((float) ($this->price_yearly ?? 0), 2, '.', '') : null,
            'entitlements' => $isCustom ? (is_array($this->entitlements) ? $this->entitlements : []) : null,
            'status' => $this->resolvedStatus(),
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'days_remaining' => $this->daysRemaining(),
            'is_expired' => $this->resolvedStatus() === 'expired',
            'payments' => $this->whenLoaded('payments', function () {
                return PaymentResource::collection($this->payments)->resolve();
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function resolvedStatus(): string
    {
        $status = (string) ($this->status ?? 'pending');
        if (in_array($status, ['active', 'pending'], true)
            && $this->ends_at !== null
            && $this->ends_at->isPast()
        ) {
            return 'expired';
        }

        return $status;
    }

    private function daysRemaining(): ?int
    {
        if ($this->ends_at === null) {
            return null;
        }
        $days = (int) now()->diffInDays($this->ends_at, false);

        return max(0, $days);
    }
}

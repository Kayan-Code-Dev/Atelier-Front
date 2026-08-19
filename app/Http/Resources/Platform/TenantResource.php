<?php

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];
        $endsAt = $this->subscription_ends_at;
        $daysRemaining = null;
        if ($endsAt !== null) {
            $daysRemaining = $endsAt->isPast()
                ? 0
                : (int) now()->startOfDay()->diffInDays($endsAt->copy()->startOfDay());
        }

        $custom = $this->currentCustomSubscription();
        $isCustom = $custom !== null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'database_name' => $this->database_name,
            'tenancy_db_name' => $this->database_name,
            'status' => $this->status,
            'is_active' => $this->status === 'active',
            'is_demo' => ($metadata['source'] ?? null) === 'demo',
            'demo_days' => isset($metadata['demo_days']) ? (int) $metadata['demo_days'] : null,
            'signup_channel' => $metadata['signup_channel'] ?? null,
            'days_remaining' => $daysRemaining,
            'created_by' => ! empty($metadata['created_by_admin_id']) || ! empty($metadata['created_by_admin_name']) || ! empty($metadata['created_by_admin_email'])
                ? [
                    'id' => isset($metadata['created_by_admin_id']) ? (int) $metadata['created_by_admin_id'] : null,
                    'name' => $metadata['created_by_admin_name'] ?? null,
                    'email' => $metadata['created_by_admin_email'] ?? null,
                ]
                : null,
            'plan_id' => $this->plan_id,
            'is_custom_plan' => $isCustom,
            'plan' => $isCustom
                ? [
                    'id' => null,
                    'name' => 'Custom Plan',
                    'slug' => 'custom',
                    'status' => (string) ($custom->status ?? 'active'),
                ]
                : $this->whenLoaded('plan', function (): ?array {
                    if ($this->plan === null) {
                        return null;
                    }

                    return [
                        'id' => $this->plan->id,
                        'name' => $this->plan->name,
                        'slug' => $this->plan->slug,
                        'status' => $this->plan->status,
                    ];
                }),
            'custom_subscription' => $isCustom ? [
                'id' => $custom->id,
                'monthly_price' => (string) ($custom->price_monthly ?? '0.00'),
                'yearly_price' => (string) ($custom->price_yearly ?? '0.00'),
                'billing_interval' => $custom->billing_interval ?: 'monthly',
                'starts_at' => $custom->starts_at?->toDateString(),
                'ends_at' => $custom->ends_at?->toDateString(),
                'currency' => $custom->currency ?: 'EGP',
                'status' => $custom->status,
                'features' => is_array($custom->entitlements) ? $custom->entitlements : [],
            ] : null,
            'subscription_starts_at' => $this->subscription_starts_at?->toISOString(),
            'subscription_ends_at' => $this->subscription_ends_at?->toISOString(),
            'trial_ends_at' => $this->subscription_ends_at?->toISOString(),
            'metadata' => $this->metadata,
            'email' => $metadata['admin_email'] ?? null,
            'admin_email' => $metadata['admin_email'] ?? null,
            'admin_name' => $metadata['admin_name'] ?? null,
            'phone' => $metadata['phone'] ?? null,
            'customer_phone' => $metadata['phone'] ?? null,
            'crm_lead_id' => $metadata['crm_lead_id'] ?? null,
            'domains' => TenantDomainResource::collection($this->whenLoaded('domains')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

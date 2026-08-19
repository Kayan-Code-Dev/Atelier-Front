<?php

namespace App\Http\Resources\Platform;

use App\Support\PlanCurrency;
use App\Support\PlanFeatureCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $features = $this->relationLoaded('features')
            ? PlanFeatureResource::collection($this->features)->resolve()
            : [];

        $featureMap = [];
        foreach ($this->features ?? [] as $feature) {
            $featureMap[$feature->feature_key] = $feature->feature_value;
        }

        $currency = PlanCurrency::normalize($this->currency ?? 'EGP');

        $maxBranches = (int) ($featureMap['branches.max'] ?? 0);
        $maxEmployees = (int) ($featureMap['users.max'] ?? 0);
        $maxInvoicesSale = (int) ($featureMap['invoices.sale.max'] ?? 0);
        $maxInvoicesRent = (int) ($featureMap['invoices.rent.max'] ?? 0);
        $maxInvoicesTailoring = (int) ($featureMap['invoices.tailoring.max'] ?? 0);
        $maxAssistantMessages = (int) ($featureMap['smart_assistant.messages_monthly.max'] ?? 0);

        $enabledFeatures = collect($features)
            ->filter(function (array $feature): bool {
                $key = (string) $feature['feature_key'];
                if (! PlanFeatureCatalog::isBooleanKey($key)) {
                    return false;
                }

                return in_array(strtolower((string) $feature['feature_value']), ['1', 'true', 'yes', 'enabled'], true);
            })
            ->pluck('feature_key')
            ->values()
            ->all();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'title' => $this->name,
            'description' => $this->description,
            'price' => number_format((float) $this->price, 2, '.', ''),
            'currency' => $currency,
            'currency_symbol' => PlanCurrency::symbol($currency),
            'currency_label' => PlanCurrency::label($currency),
            'billing_cycle' => $this->billing_cycle,
            'duration_days' => (int) ($this->duration_days ?? 365),
            'days' => (int) ($this->duration_days ?? 365),
            'status' => $this->status,
            'is_active' => $this->status === 'active',
            'sort_order' => (int) ($this->sort_order ?? 0),
            'features' => $features,
            'feature_map' => $featureMap,
            'enabled_features' => $enabledFeatures,
            'features_count' => count($enabledFeatures),
            'max_branches' => $maxBranches,
            'max_employees' => $maxEmployees,
            'max_invoices_sale' => $maxInvoicesSale,
            'max_invoices_rent' => $maxInvoicesRent,
            'max_invoices_tailoring' => $maxInvoicesTailoring,
            'max_assistant_messages' => $maxAssistantMessages,
            'tenants_count' => $this->whenCounted('tenants'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

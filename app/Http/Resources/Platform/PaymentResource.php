<?php

namespace App\Http\Resources\Platform;

use App\Support\PlanCurrency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currency = PlanCurrency::normalize(
            $this->currency
            ?? $this->plan?->currency
            ?? 'EGP'
        );

        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'tenant_name' => $this->tenant?->name,
            'tenant_slug' => $this->tenant?->slug,
            'plan_id' => $this->plan_id,
            'plan_name' => $this->plan?->name,
            'payment_gateway_id' => $this->payment_gateway_id,
            'payment_gateway_name' => $this->paymentGateway?->name,
            'purpose' => $this->purpose,
            'amount' => number_format((float) $this->amount, 2, '.', ''),
            'currency' => $currency,
            'currency_symbol' => PlanCurrency::symbol($currency),
            'method' => $this->method,
            'reference' => $this->reference,
            'status' => $this->status,
            'paid_at' => $this->paid_at?->toISOString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

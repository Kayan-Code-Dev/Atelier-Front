<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalPurchases = $this->total_purchase_orders ?? $this->total_purchases ?? 0;
        $totalPaid = $this->total_paid ?? $this->total_payment ?? 0;
        $totalRemaining = $this->total_remaining ?? $this->remaining ?? 0;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'email' => $this->email,
            'address' => $this->address,
            'tax_number' => $this->tax_number,
            'opening_balance' => $this->opening_balance,
            'current_balance' => $this->current_balance,
            'total_purchase_orders' => $totalPurchases,
            'total_purchases' => $totalPurchases,
            'total_paid' => $totalPaid,
            'total_payment' => $totalPaid,
            'total_remaining' => $totalRemaining,
            'remaining' => $totalRemaining,
            'orders_count' => (int) ($this->orders_count ?? $this->purchases_count ?? 0),
            'purchases_count' => (int) ($this->orders_count ?? $this->purchases_count ?? 0),
            'notes' => $this->notes,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}

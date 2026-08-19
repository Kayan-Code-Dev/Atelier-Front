<?php

namespace App\Http\Requests\Tenant\PurchaseOrder;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists('tenant.suppliers', 'id')->whereNull('deleted_at')],
            'branch_id' => ['nullable', 'integer', Rule::exists('tenant.branches', 'id')->whereNull('deleted_at')],
            'category_id' => ['nullable', 'integer', Rule::exists('tenant.dress_categories', 'id')->whereNull('deleted_at')],
            'subcategory_id' => ['nullable', 'integer', Rule::exists('tenant.dress_categories', 'id')->whereNull('deleted_at')],
            'status' => ['nullable', 'string', Rule::in(PurchaseOrderStatus::values())],
            'type' => ['nullable', 'string', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_method' => ['nullable', 'string', 'in:cash,bank_transfer,check'],
            'cashbox_id' => ['nullable', 'integer', Rule::exists('tenant.cashboxes', 'id')->whereNull('deleted_at')],
            'order_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['nullable', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.dress_category_id' => ['nullable', 'integer'],
            'items.*.dress_subcategory_id' => ['nullable', 'integer'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items');
        if (! is_array($items)) {
            return;
        }

        $normalized = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $quantity = max(0.01, (float) ($item['quantity'] ?? 1));
            $unitPrice = max(0, (float) ($item['unit_price'] ?? $item['price'] ?? 0));
            $normalized[] = array_merge($item, [
                'item_name' => trim((string) ($item['item_name'] ?? $item['code'] ?? 'صنف مورد')),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ]);
        }

        $payload = [
            'items' => $normalized,
            'tax' => max(0, (float) ($this->input('tax') ?? 0)),
            'discount' => max(0, (float) ($this->input('discount') ?? 0)),
        ];

        if ($this->exists('deposit_amount') || $this->exists('payment_amount')) {
            $payload['deposit_amount'] = max(0, (float) ($this->input('deposit_amount') ?? $this->input('payment_amount') ?? 0));
        }

        $this->merge($payload);
    }
}

<?php

namespace App\Http\Requests\Tenant\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class StoreFixedAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'asset_code' => ['nullable', 'string', 'max:50'],
            'category_id' => ['required', 'integer'],
            'branch_id' => ['nullable', 'integer'],
            'location' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string'],
            'purchase_date' => ['required', 'date'],
            'placed_in_service_date' => ['nullable', 'date'],
            'acquisition_cost' => ['required', 'numeric', 'min:0'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life' => ['required', 'integer', 'min:1'],
            'useful_life_unit' => ['nullable', 'in:months,years'],
            'depreciation_method' => ['nullable', 'in:straight_line'],
            'acquisition_method' => ['nullable', 'in:cash,payable'],
            'payment_account_id' => ['nullable', 'integer'],
            'asset_account_id' => ['nullable', 'integer'],
            'post_purchase' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
        ];
    }
}

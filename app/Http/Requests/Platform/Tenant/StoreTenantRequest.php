<?php

namespace App\Http\Requests\Platform\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('custom_plan')) {
            $this->merge([
                'custom_plan' => filter_var($this->input('custom_plan'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('central.tenants', 'slug')],
            'database_name' => ['nullable', 'string', 'max:255', Rule::unique('central.tenants', 'database_name')],
            'plan_id' => ['required_without:custom_plan', 'nullable', 'integer', Rule::exists('central.plans', 'id')],
            'custom_plan' => ['sometimes', 'boolean'],
            'custom_subscription' => ['required_if:custom_plan,true', 'array'],
            'custom_subscription.monthly_price' => ['required_if:custom_plan,true', 'numeric', 'min:0'],
            'custom_subscription.yearly_price' => ['required_if:custom_plan,true', 'numeric', 'min:0'],
            'custom_subscription.billing_interval' => ['required_if:custom_plan,true', Rule::in(['monthly', 'yearly'])],
            'custom_subscription.starts_at' => ['nullable', 'date'],
            'custom_subscription.ends_at' => ['nullable', 'date', 'after_or_equal:custom_subscription.starts_at'],
            'custom_subscription.currency' => ['nullable', 'string', 'size:3'],
            'custom_subscription.features' => ['nullable', 'array'],
            'subscription_starts_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date', 'after_or_equal:subscription_starts_at'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

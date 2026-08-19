<?php

namespace App\Http\Requests\Tenant\Cashbox;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => ['nullable', 'integer', Rule::exists('tenant.branches', 'id')->whereNull('deleted_at')],
            'kind' => ['nullable', 'string', 'in:cash,bank,wallet'],
            'account_id' => ['nullable', 'integer', Rule::exists('tenant.accounts', 'id')],
            'initial_balance' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

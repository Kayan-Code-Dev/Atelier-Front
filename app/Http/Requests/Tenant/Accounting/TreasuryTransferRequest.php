<?php

namespace App\Http\Requests\Tenant\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class TreasuryTransferRequest extends FormRequest
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
            'from_cashbox_id' => ['required', 'integer', 'exists:tenant.cashboxes,id'],
            'to_cashbox_id' => ['required', 'integer', 'exists:tenant.cashboxes,id', 'different:from_cashbox_id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'movement_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ];
    }
}

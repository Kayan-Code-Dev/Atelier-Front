<?php

namespace App\Http\Requests\Tenant\Dress;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferDressRequest extends FormRequest
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
            'to_branch_id' => [
                'required',
                'integer',
                Rule::exists('tenant.branches', 'id')->whereNull('deleted_at'),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to_branch_id.required' => 'الفرع المستهدف مطلوب.',
            'to_branch_id.exists' => 'الفرع المستهدف غير موجود.',
        ];
    }
}

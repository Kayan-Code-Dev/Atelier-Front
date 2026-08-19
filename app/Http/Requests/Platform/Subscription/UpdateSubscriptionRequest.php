<?php

namespace App\Http\Requests\Platform\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionRequest extends FormRequest
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
            'plan_id' => ['sometimes', 'nullable', 'integer', 'exists:plans,id'],
            'status' => ['sometimes', 'string', Rule::in(['pending', 'active', 'cancelled', 'expired', 'rejected'])],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}

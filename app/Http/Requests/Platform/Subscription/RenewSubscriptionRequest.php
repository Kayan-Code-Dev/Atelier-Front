<?php

namespace App\Http\Requests\Platform\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class RenewSubscriptionRequest extends FormRequest
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
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'months' => ['nullable', 'integer', 'min:1', 'max:120'],
        ];
    }
}

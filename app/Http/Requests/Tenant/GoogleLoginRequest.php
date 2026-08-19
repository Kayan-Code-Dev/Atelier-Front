<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class GoogleLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string', 'min:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_token.required' => 'رمز Google مطلوب.',
        ];
    }
}

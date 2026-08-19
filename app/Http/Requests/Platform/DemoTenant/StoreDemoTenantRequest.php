<?php

namespace App\Http\Requests\Platform\DemoTenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemoTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'days' => ['required', 'integer', 'min:1', 'max:7'],
        ];
    }
}

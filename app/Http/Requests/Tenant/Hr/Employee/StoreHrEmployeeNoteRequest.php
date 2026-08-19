<?php

namespace App\Http\Requests\Tenant\Hr\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHrEmployeeNoteRequest extends FormRequest
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
            'type' => ['nullable', 'string', Rule::in(['hr', 'performance', 'warning'])],
            'content' => ['required', 'string', 'max:2000'],
        ];
    }
}

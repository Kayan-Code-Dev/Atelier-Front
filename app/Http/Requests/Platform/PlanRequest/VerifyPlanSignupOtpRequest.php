<?php

namespace App\Http\Requests\Platform\PlanRequest;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPlanSignupOtpRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'string', 'size:6'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'otp.required' => 'يرجى إدخال رمز التحقق.',
            'otp.size' => 'رمز التحقق يجب أن يكون 6 أرقام.',
        ];
    }
}

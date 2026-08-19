<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLandingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'working_hours' => ['nullable', 'string', 'max:120'],
            'facebook_url' => ['nullable', 'string', 'max:500'],
            'instagram_url' => ['nullable', 'string', 'max:500'],
            'twitter_url' => ['nullable', 'string', 'max:500'],
            'linkedin_url' => ['nullable', 'string', 'max:500'],
            'tiktok_url' => ['nullable', 'string', 'max:500'],
            'youtube_url' => ['nullable', 'string', 'max:500'],
            'footer_copyright' => ['nullable', 'string', 'max:255'],
            'modules' => ['nullable', 'array', 'max:30'],
            'modules.*.icon' => ['nullable', 'string', 'max:80'],
            'modules.*.label' => ['required_with:modules', 'string', 'max:80'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'modules.max' => 'لا يمكن إضافة أكثر من 30 وحدة.',
            'modules.*.label.required_with' => 'اسم الوحدة مطلوب.',
        ];
    }
}

<?php

namespace App\Http\Requests\PublicApi;

use Illuminate\Foundation\Http\FormRequest;

class StoreCareerApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = (int) config('recruitment.cv_max_kilobytes', 5120);
        $portfolioKb = (int) config('recruitment.portfolio_max_kilobytes', 8192);

        return [
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:120'],
            'linkedin_url' => ['nullable', 'url', 'max:500'],
            'portfolio_url' => ['nullable', 'url', 'max:500'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:50'],
            'specialty' => ['nullable', 'string', 'max:180'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
            'cv' => ['required', 'file', 'max:'.$maxKb],
            'portfolio_file' => ['nullable', 'file', 'max:'.$portfolioKb],
            'website' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'الاسم الكامل مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'consent.accepted' => 'يجب الموافقة على سياسة الخصوصية.',
            'cv.required' => 'يرجى إرفاق السيرة الذاتية.',
            'cv.max' => 'حجم ملف السيرة يتجاوز الحد المسموح.',
            'linkedin_url.url' => 'رابط LinkedIn غير صالح.',
            'portfolio_url.url' => 'رابط المعرض غير صالح.',
        ];
    }
}

<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecruitmentJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('recruitment_jobs', 'slug')],
            'department' => ['required', 'string', 'max:120'],
            'employment_type' => ['required', 'string', Rule::in(config('recruitment.employment_types'))],
            'location' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:8000'],
            'responsibilities' => ['nullable'],
            'requirements' => ['nullable'],
            'nice_to_have' => ['nullable'],
            'benefits' => ['nullable'],
            'skills' => ['nullable'],
            'status' => ['nullable', 'string', Rule::in(config('recruitment.job_statuses'))],
        ];
    }
}

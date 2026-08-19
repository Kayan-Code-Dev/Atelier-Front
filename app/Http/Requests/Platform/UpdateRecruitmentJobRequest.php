<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecruitmentJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('recruitment_jobs', 'slug')->ignore($id)],
            'department' => ['sometimes', 'required', 'string', 'max:120'],
            'employment_type' => ['sometimes', 'required', 'string', Rule::in(config('recruitment.employment_types'))],
            'location' => ['sometimes', 'required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:8000'],
            'responsibilities' => ['nullable'],
            'requirements' => ['nullable'],
            'nice_to_have' => ['nullable'],
            'benefits' => ['nullable'],
            'skills' => ['nullable'],
            'status' => ['sometimes', 'string', Rule::in(config('recruitment.job_statuses'))],
        ];
    }
}

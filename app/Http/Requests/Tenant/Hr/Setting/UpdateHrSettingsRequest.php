<?php

namespace App\Http\Requests\Tenant\Hr\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHrSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.attendance_rules' => ['sometimes', 'array'],
            'settings.payroll_rules' => ['sometimes', 'array'],
            'settings.leave_rules' => ['sometimes', 'array'],
            'settings.document_rules' => ['sometimes', 'array'],
            // Accept extra known HR prefs without stripping the parent `settings` bag.
            'settings.weekend_days' => ['sometimes', 'array'],
            'settings.weekend_days.*' => ['string', 'max:20'],
        ];
    }

    /**
     * Keep full settings payload even when only optional nested keys are present.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if ($key !== null) {
            return $validated;
        }

        if (! array_key_exists('settings', $validated) && $this->has('settings')) {
            $validated['settings'] = (array) $this->input('settings', []);
        }

        return $validated;
    }
}

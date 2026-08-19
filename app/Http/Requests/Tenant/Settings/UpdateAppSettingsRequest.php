<?php

namespace App\Http\Requests\Tenant\Settings;

use App\Services\Tenant\AppSettingService;
use App\Support\PlanCurrency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ruleKeys = AppSettingService::INVOICE_RULE_KEYS;

        $rules = [
            'currency' => ['sometimes', 'string', Rule::in(PlanCurrency::SUPPORTED)],
            'timezone' => ['sometimes', 'string', 'max:100'],
            'invoice' => ['sometimes', 'array'],
            'invoice.show_tax' => ['sometimes', 'boolean'],
            'invoice.show_logo' => ['sometimes', 'boolean'],
            'invoice.show_discount' => ['sometimes', 'boolean'],
            'invoice.show_customer_rules' => ['sometimes', 'boolean'],
            'invoice.show_workshop_notes' => ['sometimes', 'boolean'],
            'invoice.template' => ['sometimes', 'string', Rule::in(AppSettingService::INVOICE_TEMPLATES)],
            'invoice.footer_text' => ['sometimes', 'string', 'max:500'],
            'invoice.rules' => ['sometimes', 'array'],
        ];

        foreach ($ruleKeys as $key) {
            $rules["invoice.rules.{$key}"] = ['sometimes', 'array', 'max:20'];
            $rules["invoice.rules.{$key}.*"] = ['string', 'max:500'];
        }

        return $rules;
    }
}

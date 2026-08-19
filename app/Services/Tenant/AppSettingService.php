<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Setting;
use App\Support\PlanCurrency;
use Throwable;

class AppSettingService
{
    public const KEY_APP_SETTINGS = 'app.settings';

    public const INVOICE_TEMPLATES = ['premium', 'classic', 'compact'];

    public const INVOICE_RULE_KEYS = [
        'rental_customer',
        'rental_workshop',
        'sale_customer',
        'sale_workshop',
        'tailoring_customer',
        'tailoring_workshop',
    ];

    public function defaults(): array
    {
        return [
            'timezone' => 'UTC',
            'currency' => $this->resolvePlanCurrencyFallback(),
            'invoice' => self::defaultInvoiceSettings(),
        ];
    }

    public static function defaultInvoiceSettings(): array
    {
        return [
            'show_tax' => true,
            'show_logo' => true,
            'show_discount' => true,
            'show_customer_rules' => true,
            'show_workshop_notes' => true,
            'template' => 'premium',
            'footer_text' => 'DressnMore — طباعة منظمة للفرع والورشة',
            'rules' => self::emptyInvoiceRules(),
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function emptyInvoiceRules(): array
    {
        $rules = [];
        foreach (self::INVOICE_RULE_KEYS as $key) {
            $rules[$key] = [];
        }

        return $rules;
    }

    public function all(): array
    {
        $stored = Setting::query()->where('key', self::KEY_APP_SETTINGS)->value('value');

        $merged = array_merge(
            $this->defaults(),
            is_array($stored) ? $stored : [],
        );

        $merged['invoice'] = self::normalizeInvoiceSettings(
            is_array($merged['invoice'] ?? null) ? $merged['invoice'] : [],
        );

        return $merged;
    }

    public function currency(): string
    {
        return PlanCurrency::normalizeTenant($this->present()['currency'] ?? 'EGP');
    }

    public function present(): array
    {
        $settings = $this->all();
        $currency = PlanCurrency::normalizeTenant((string) ($settings['currency'] ?? 'EGP'));

        if (
            $currency === 'USD'
            && ($settings['timezone'] ?? 'UTC') === 'UTC'
            && ($settings['currency'] ?? null) === 'USD'
        ) {
            $planCurrency = $this->resolvePlanCurrencyFallback();
            if ($planCurrency !== 'USD') {
                $currency = PlanCurrency::normalizeTenant($planCurrency);
            }
        }

        $invoice = self::normalizeInvoiceSettings(
            is_array($settings['invoice'] ?? null) ? $settings['invoice'] : [],
        );

        return [
            'timezone' => (string) ($settings['timezone'] ?? 'UTC'),
            'currency' => $currency,
            'currency_symbol' => PlanCurrency::symbol($currency),
            'currency_label' => PlanCurrency::label($currency),
            'invoice' => $invoice,
        ];
    }

    public function update(array $data): array
    {
        $current = $this->all();

        if (array_key_exists('timezone', $data)) {
            $current['timezone'] = trim((string) $data['timezone']) ?: 'UTC';
        }

        if (array_key_exists('currency', $data)) {
            $current['currency'] = PlanCurrency::normalizeTenant((string) $data['currency']);
        }

        if (array_key_exists('invoice', $data) && is_array($data['invoice'])) {
            $invoice = self::normalizeInvoiceSettings(
                is_array($current['invoice'] ?? null) ? $current['invoice'] : [],
            );

            foreach (['show_tax', 'show_logo', 'show_discount', 'show_customer_rules', 'show_workshop_notes'] as $key) {
                if (array_key_exists($key, $data['invoice'])) {
                    $invoice[$key] = (bool) $data['invoice'][$key];
                }
            }

            if (array_key_exists('template', $data['invoice'])) {
                $template = strtolower(trim((string) $data['invoice']['template']));
                $invoice['template'] = in_array($template, self::INVOICE_TEMPLATES, true)
                    ? $template
                    : 'premium';
            }

            if (array_key_exists('footer_text', $data['invoice'])) {
                $invoice['footer_text'] = mb_substr(trim((string) $data['invoice']['footer_text']), 0, 500);
            }

            if (array_key_exists('rules', $data['invoice']) && is_array($data['invoice']['rules'])) {
                foreach (self::INVOICE_RULE_KEYS as $ruleKey) {
                    if (! array_key_exists($ruleKey, $data['invoice']['rules'])) {
                        continue;
                    }
                    $raw = $data['invoice']['rules'][$ruleKey];
                    if (! is_array($raw)) {
                        continue;
                    }
                    $invoice['rules'][$ruleKey] = self::sanitizeRuleList($raw);
                }
            }

            $current['invoice'] = $invoice;
        }

        Setting::query()->updateOrCreate(
            ['key' => self::KEY_APP_SETTINGS],
            ['value' => $current],
        );

        return $this->present();
    }

    /**
     * @param  array<string, mixed>  $invoice
     * @return array{
     *   show_tax: bool,
     *   show_logo: bool,
     *   show_discount: bool,
     *   show_customer_rules: bool,
     *   show_workshop_notes: bool,
     *   template: string,
     *   footer_text: string,
     *   rules: array<string, list<string>>
     * }
     */
    public static function normalizeInvoiceSettings(array $invoice): array
    {
        $defaults = self::defaultInvoiceSettings();
        $merged = array_merge($defaults, $invoice);

        $template = strtolower(trim((string) ($merged['template'] ?? 'premium')));
        if (! in_array($template, self::INVOICE_TEMPLATES, true)) {
            $template = 'premium';
        }

        $rulesInput = is_array($merged['rules'] ?? null) ? $merged['rules'] : [];
        $rules = self::emptyInvoiceRules();
        foreach (self::INVOICE_RULE_KEYS as $key) {
            if (isset($rulesInput[$key]) && is_array($rulesInput[$key])) {
                $rules[$key] = self::sanitizeRuleList($rulesInput[$key]);
            }
        }

        return [
            'show_tax' => (bool) ($merged['show_tax'] ?? true),
            'show_logo' => (bool) ($merged['show_logo'] ?? true),
            'show_discount' => (bool) ($merged['show_discount'] ?? true),
            'show_customer_rules' => (bool) ($merged['show_customer_rules'] ?? true),
            'show_workshop_notes' => (bool) ($merged['show_workshop_notes'] ?? true),
            'template' => $template,
            'footer_text' => mb_substr(trim((string) ($merged['footer_text'] ?? $defaults['footer_text'])), 0, 500),
            'rules' => $rules,
        ];
    }

    /**
     * @param  list<mixed>  $raw
     * @return list<string>
     */
    private static function sanitizeRuleList(array $raw): array
    {
        $out = [];
        foreach ($raw as $item) {
            $text = trim((string) $item);
            if ($text === '') {
                continue;
            }
            $out[] = mb_substr($text, 0, 500);
            if (count($out) >= 20) {
                break;
            }
        }

        return $out;
    }

    private function resolvePlanCurrencyFallback(): string
    {
        try {
            $tenant = app(TenantContext::class)->tenant();
            if ($tenant !== null) {
                $tenant->loadMissing('plan');

                return PlanCurrency::normalize($tenant->plan?->currency ?? 'EGP');
            }
        } catch (Throwable) {
            // Tenant context may be unavailable outside HTTP/tenant boot.
        }

        return 'EGP';
    }
}

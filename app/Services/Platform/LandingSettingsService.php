<?php

namespace App\Services\Platform;

use App\Models\Central\LandingSetting;
use Illuminate\Support\Facades\Cache;

class LandingSettingsService
{
    public const CACHE_KEY = 'platform.landing.settings';

    /**
     * @return list<array{icon: string, label: string}>
     */
    public static function defaultModules(): array
    {
        return [
            ['icon' => 'ri-truck-line', 'label' => 'الموردون'],
            ['icon' => 'ri-group-line', 'label' => 'العملاء'],
            ['icon' => 'ri-team-line', 'label' => 'الموظفون'],
            ['icon' => 'ri-archive-line', 'label' => 'المخزون'],
            ['icon' => 'ri-receipt-line', 'label' => 'الفواتير'],
            ['icon' => 'ri-scissors-cut-line', 'label' => 'التفصيل'],
            ['icon' => 'ri-home-gear-line', 'label' => 'الإيجار'],
            ['icon' => 'ri-calculator-line', 'label' => 'المحاسبة'],
            ['icon' => 'ri-exchange-line', 'label' => 'نقل المخزون'],
            ['icon' => 'ri-building-line', 'label' => 'الفروع'],
            ['icon' => 'ri-tools-line', 'label' => 'الورشة'],
            ['icon' => 'ri-bar-chart-2-line', 'label' => 'التقارير'],
            ['icon' => 'ri-chat-smile-3-line', 'label' => 'الشات الذكي'],
            ['icon' => 'ri-robot-2-line', 'label' => 'المساعد الذكي'],
            ['icon' => 'ri-global-line', 'label' => 'الموقع الإلكتروني'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'company_name' => 'DressnMore',
            'phone' => '+966 50 000 0000',
            'whatsapp' => '00201070205189',
            'email' => 'info@dressnmore.com',
            'address' => 'الرياض، المملكة العربية السعودية',
            'working_hours' => 'الأحد - الخميس، 9ص - 6م',
            'facebook_url' => '',
            'instagram_url' => '',
            'twitter_url' => '',
            'linkedin_url' => '',
            'tiktok_url' => '',
            'youtube_url' => '',
            'footer_copyright' => '© 2025 DressnMore. جميع الحقوق محفوظة',
            'modules' => self::defaultModules(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publicPayload(): array
    {
        return Cache::remember(self::CACHE_KEY, 60, function (): array {
            return $this->toPayload($this->current());
        });
    }

    public function current(): LandingSetting
    {
        $row = LandingSetting::query()->orderBy('id')->first();
        if ($row !== null) {
            return $row;
        }

        return LandingSetting::query()->create(self::defaults());
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function update(array $input): array
    {
        $row = $this->current();
        $defaults = self::defaults();
        $fill = [];

        if (array_key_exists('company_name', $input)) {
            $fill['company_name'] = $this->stringOrDefault($input['company_name'], (string) $defaults['company_name']);
        }
        foreach (['phone', 'whatsapp', 'email', 'address', 'working_hours', 'footer_copyright'] as $key) {
            if (array_key_exists($key, $input)) {
                $fill[$key] = $this->nullableString($input[$key]);
            }
        }
        foreach (['facebook_url', 'instagram_url', 'twitter_url', 'linkedin_url', 'tiktok_url', 'youtube_url'] as $key) {
            if (array_key_exists($key, $input)) {
                $fill[$key] = $this->nullableUrl($input[$key]);
            }
        }
        if (array_key_exists('modules', $input)) {
            $fill['modules'] = $this->normalizeModules($input['modules']);
        }

        if ($fill !== []) {
            $row->fill($fill);
            $row->save();
            Cache::forget(self::CACHE_KEY);
        }

        return $this->toPayload($row->fresh() ?? $row);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(LandingSetting $row): array
    {
        $defaults = self::defaults();
        $modules = $this->normalizeModules($row->modules);

        $whatsapp = trim((string) ($row->whatsapp ?: $defaults['whatsapp']));

        return [
            'company_name' => trim((string) ($row->company_name ?: $defaults['company_name'])),
            'phone' => trim((string) ($row->phone ?: $defaults['phone'])),
            'whatsapp' => $whatsapp,
            'whatsapp_href' => self::whatsappHref($whatsapp),
            'email' => trim((string) ($row->email ?: $defaults['email'])),
            'address' => trim((string) ($row->address ?: $defaults['address'])),
            'working_hours' => trim((string) ($row->working_hours ?: $defaults['working_hours'])),
            'facebook_url' => trim((string) ($row->facebook_url ?? '')),
            'instagram_url' => trim((string) ($row->instagram_url ?? '')),
            'twitter_url' => trim((string) ($row->twitter_url ?? '')),
            'linkedin_url' => trim((string) ($row->linkedin_url ?? '')),
            'tiktok_url' => trim((string) ($row->tiktok_url ?? '')),
            'youtube_url' => trim((string) ($row->youtube_url ?? '')),
            'footer_copyright' => trim((string) ($row->footer_copyright ?: $defaults['footer_copyright'])),
            'modules' => $modules,
            'modules_count' => count($modules),
        ];
    }

    public static function whatsappHref(string $whatsapp): string
    {
        $digits = preg_replace('/\D+/', '', $whatsapp) ?? '';
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if ($digits === '') {
            return '#';
        }

        return 'https://wa.me/'.$digits;
    }

    /**
     * @param  mixed  $raw
     * @return list<array{icon: string, label: string}>
     */
    private function normalizeModules(mixed $raw): array
    {
        if (! is_array($raw) || $raw === []) {
            return self::defaultModules();
        }

        $out = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $icon = trim((string) ($item['icon'] ?? 'ri-apps-line'));
            if ($icon === '') {
                $icon = 'ri-apps-line';
            }
            $out[] = [
                'icon' => mb_substr($icon, 0, 80),
                'label' => mb_substr($label, 0, 80),
            ];
            if (count($out) >= 30) {
                break;
            }
        }

        return $out === [] ? self::defaultModules() : $out;
    }

    private function stringOrDefault(mixed $value, string $default): string
    {
        $trimmed = trim((string) ($value ?? ''));

        return $trimmed !== '' ? mb_substr($trimmed, 0, 120) : $default;
    }

    private function nullableString(mixed $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));

        return $trimmed === '' ? null : mb_substr($trimmed, 0, 255);
    }

    private function nullableUrl(mixed $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));
        if ($trimmed === '' || $trimmed === '#') {
            return null;
        }

        return mb_substr($trimmed, 0, 500);
    }
}

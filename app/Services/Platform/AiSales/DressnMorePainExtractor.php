<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

/**
 * Pain points are stored as known | inferred | unknown. Never invent unsupported pains.
 */
final class DressnMorePainExtractor
{
    /**
     * @param  list<array{key: string, status: string, evidence?: string}>  $existing
     * @return list<array{key: string, status: string, evidence: string}>
     */
    public function extract(string $text, array $existing = []): array
    {
        $hay = mb_strtolower(trim($text));
        $byKey = [];
        foreach ($existing as $row) {
            if (isset($row['key'])) {
                $byKey[$row['key']] = $row;
            }
        }

        foreach ($this->catalog() as $key => $needles) {
            if ($this->has($hay, $needles)) {
                $byKey[$key] = [
                    'key' => $key,
                    'status' => 'known',
                    'evidence' => mb_substr($text, 0, 180),
                ];
            }
        }

        return array_values($byKey);
    }

    /**
     * @return array<string, list<string>>
     */
    private function catalog(): array
    {
        return [
            'manual_invoices' => ['فاتورة يدوي', 'فواتير يدوي', 'excel', 'إكسل', 'اكسل'],
            'lost_customer_data' => ['بيانات العملاء', 'ضيعت عميل', 'lost customer'],
            'reservation_confusion' => ['حجز', 'حجوزات', 'reservation', 'booking'],
            'rental_tracking' => ['إيجار', 'تأجير', 'rental'],
            'inventory_problems' => ['مخزون', 'inventory'],
            'supplier_management' => ['مورد', 'supplier'],
            'accounting_problems' => ['محاسبة', 'accounting'],
            'multiple_branches' => ['فروع', 'فرعين', 'multi-branch', 'branches'],
            'employee_management' => ['موظفين', 'hr', 'موظفين'],
            'website_needs' => ['موقع', 'website'],
            'social_integration' => ['انستجرام', 'فيسبوك', 'instagram', 'facebook'],
            'reporting' => ['تقارير', 'reports'],
            'workshop_management' => ['ورشة', 'تفصيل', 'workshop', 'tailoring'],
            'production' => ['مصنع', 'factory', 'إنتاج'],
        ];
    }

    /**
     * @param  list<string>  $needles
     */
    private function has(string $hay, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && mb_strpos($hay, mb_strtolower($needle)) !== false) {
                return true;
            }
        }

        return false;
    }
}

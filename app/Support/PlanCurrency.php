<?php

namespace App\Support;

final class PlanCurrency
{
    public const SUPPORTED = [
        'EGP',
        'USD',
        'SYP',
        'SAR',
        'AED',
        'JOD',
        'KWD',
        'ILS',
        'TRY',
        'EUR',
    ];

    public static function normalize(?string $currency): string
    {
        $code = strtoupper(trim((string) $currency));

        return in_array($code, self::SUPPORTED, true) ? $code : 'EGP';
    }

    public static function normalizeTenant(?string $currency): string
    {
        return self::normalize($currency);
    }

    public static function symbol(?string $currency): string
    {
        return match (self::normalize($currency)) {
            'EGP' => 'ج.م',
            'USD' => '$',
            'SYP' => 'ل.س',
            'SAR' => 'ر.س',
            'AED' => 'د.إ',
            'JOD' => 'د.أ',
            'KWD' => 'د.ك',
            'ILS' => '₪',
            'TRY' => '₺',
            'EUR' => '€',
            default => 'ج.م',
        };
    }

    public static function label(?string $currency): string
    {
        return match (self::normalize($currency)) {
            'EGP' => 'جنيه مصري',
            'USD' => 'دولار',
            'SYP' => 'ليرة سورية',
            'SAR' => 'ريال سعودي',
            'AED' => 'درهم إماراتي',
            'JOD' => 'دينار أردني',
            'KWD' => 'دينار كويتي',
            'ILS' => 'شيكل',
            'TRY' => 'ليرة تركية',
            'EUR' => 'يورو أوروبي',
            default => 'جنيه مصري',
        };
    }
}

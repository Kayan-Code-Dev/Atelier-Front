<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales\Identity;

final class PhoneIdentity
{
    public static function digits(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = '20'.substr($digits, 1);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '20'.$digits;
        }

        return $digits;
    }

    public static function matchKey(?string $phone): string
    {
        $digits = self::digits($phone);
        if ($digits === '') {
            return '';
        }
        if (strlen($digits) > 10) {
            return substr($digits, -10);
        }

        return $digits;
    }

    public static function display(?string $phone): ?string
    {
        $digits = self::digits($phone);
        if ($digits === '') {
            return null;
        }
        if (! str_starts_with($digits, '+')) {
            return '+'.$digits;
        }

        return $digits;
    }

    public static function matches(?string $left, ?string $right): bool
    {
        $a = self::matchKey($left);
        $b = self::matchKey($right);

        return $a !== '' && $a === $b;
    }
}

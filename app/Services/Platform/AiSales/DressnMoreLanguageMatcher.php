<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

final class DressnMoreLanguageMatcher
{
    public function detect(string $text): string
    {
        $hay = mb_strtolower(trim($text));
        if ($hay === '') {
            return 'egyptian_ar';
        }

        $latin = preg_match_all('/[A-Za-z]/u', $hay) ?: 0;
        $arabic = preg_match_all('/[\x{0600}-\x{06FF}]/u', $hay) ?: 0;
        if ($latin > 0 && $latin >= $arabic * 2) {
            return 'en';
        }

        $egyptian = ['عندك', 'بتدير', 'بتديروا', 'عايز', 'عاوز', 'كام', 'خليني', 'لوحدي', 'فرعين', 'دلوقتي', 'هكلمك', 'الأتيليه', 'الانيليه', 'مش', 'إيه', 'ايه'];
        foreach ($egyptian as $marker) {
            if (mb_strpos($hay, $marker) !== false) {
                return 'egyptian_ar';
            }
        }

        return 'ar';
    }

    public function questionLocale(string $detected): string
    {
        return $detected === 'en' ? 'en' : 'egyptian_ar';
    }
}

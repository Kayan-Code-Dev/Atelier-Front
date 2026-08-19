<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales;

/**
 * Extracts structured business facts from natural language. Never invents missing numbers.
 */
final class DressnMoreProfileExtractor
{
    /**
     * @param  array<string, mixed>  $known
     * @return array<string, mixed>
     */
    public function extract(string $text, array $known = []): array
    {
        $hay = mb_strtolower(trim($text));
        $out = $known;

        $branches = $this->parseBranches($hay);
        if ($branches !== null) {
            $out['branches'] = $branches;
        }

        $users = $this->parseUsers($hay);
        if ($users !== null) {
            $out['users'] = $users;
        }

        $invoices = $this->parseInvoices($hay);
        if ($invoices !== null) {
            $out['invoice_volume'] = $invoices;
        }

        $system = $this->parseCurrentSystem($hay);
        if ($system !== null) {
            $out['current_system'] = $system;
        }

        $features = $this->parseFeatures($hay);
        if ($features !== []) {
            $out['desired_features'] = array_values(array_unique(array_merge(
                is_array($out['desired_features'] ?? null) ? $out['desired_features'] : [],
                $features,
            )));
        }

        if ($this->has($hay, ['أتيليه', 'اتيليه', 'atelier', 'فستان', 'فساتين'])) {
            $out['business_type'] = 'atelier';
        }

        return $out;
    }

    private function parseBranches(string $hay): ?int
    {
        if ($this->has($hay, ['كام فرع', 'كم فرع', 'how many branches', 'عدد الفروع'])) {
            // A question about branches is not an answer.
            if (! preg_match('/(\d+|واحد|فرعين|اتنين|اثنين)\s*(فرع|فروع|branches?)?/u', $hay)) {
                return null;
            }
        }
        if ($this->has($hay, ['فرع واحد', 'فرعي واحد', 'فرع بس', 'فرع فقط', 'one branch', '1 branch', 'single branch', 'فرع وحيد'])) {
            return 1;
        }
        if ($this->has($hay, ['فرعين', 'فرعان', 'two branches', '2 branches'])) {
            return 2;
        }
        if (preg_match('/(\d+)\s*(فرع|فروع|branches?)/u', $hay, $m)) {
            return max(1, (int) $m[1]);
        }
        if (preg_match('/عندي\s+فرع(?!\s*(جديد|تاني|آخر|اخر))/u', $hay)) {
            return 1;
        }

        return null;
    }

    public function parseShortBranchAnswer(string $text): ?int
    {
        $hay = trim(mb_strtolower($text));
        $hay = trim($hay, " \t\n\r.،!");
        $map = [
            'واحد' => 1,
            'واحدة' => 1,
            'فرع' => 1,
            'فرع واحد' => 1,
            '1' => 1,
            '٢' => 2,
            '2' => 2,
            'اتنين' => 2,
            'اثنين' => 2,
            'فرعين' => 2,
            'تلاتة' => 3,
            'ثلاثة' => 3,
            '3' => 3,
        ];
        if (isset($map[$hay])) {
            return $map[$hay];
        }
        if (preg_match('/^(\d+)\s*(فرع|فروع)?$/u', $hay, $m)) {
            return max(1, (int) $m[1]);
        }

        return $this->parseBranches($hay);
    }

    private function parseUsers(string $hay): ?int
    {
        if ($this->has($hay, ['لوحدي', 'وحدي', 'أنا بس', 'انا بس', 'on my own', 'just me', 'alone'])) {
            return 1;
        }
        if (preg_match('/(\d+)\s*(موظف|موظفين|شخص|أشخاص|users?|employees?)/u', $hay, $m)) {
            return max(1, (int) $m[1]);
        }

        return null;
    }

    private function parseInvoices(string $hay): ?int
    {
        if (preg_match('/(\d+)\s*(فاتورة|فواتير|invoices?)/u', $hay, $m)) {
            return max(0, (int) $m[1]);
        }

        return null;
    }

    private function parseCurrentSystem(?string $hay): ?string
    {
        $hay = (string) $hay;
        foreach (['excel', 'إكسل', 'اكسل'] as $needle) {
            if (mb_strpos($hay, $needle) !== false) {
                return 'Excel';
            }
        }
        foreach (['whatsapp', 'واتساب', 'واتس'] as $needle) {
            if (mb_strpos($hay, $needle) !== false) {
                return 'WhatsApp';
            }
        }
        if ($this->has($hay, ['برنامج', 'نظام تاني', 'نظام آخر', 'another system', 'current software', 'عندي نظام', 'عندي برنامج'])) {
            return 'other_system';
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function parseFeatures(string $hay): array
    {
        $map = [
            'website.enabled' => ['موقع', 'website', 'ويب سايت'],
            'ai_assistant.enabled' => ['مستشار ذكي', 'advanced ai', 'ai متقدم', 'ذكاء اصطناعي'],
            'ai_assistant.advanced' => ['تحليل أعمق', 'advanced reporting', 'تقارير متقدمة'],
            'factory.enabled' => ['مصنع', 'factory', 'إنتاج'],
            'workshop.enabled' => ['ورشة', 'workshop', 'تفصيل'],
            'accounting.enabled' => ['محاسبة', 'accounting'],
            'hr.enabled' => ['موظفين hr', 'موارد بشرية', 'hr'],
            'inventory.enabled' => ['مخزون', 'inventory'],
            'reports.enabled' => ['تقارير', 'reporting', 'reports'],
        ];
        $out = [];
        foreach ($map as $key => $needles) {
            if ($this->has($hay, $needles)) {
                $out[] = $key;
            }
        }

        return $out;
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

<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales\Identity;

/**
 * Conservative person/business name extraction. Never invents a name.
 */
final class CustomerNameExtractor
{
    /**
     * @return array{
     *   customer_name:?string,
     *   business_name:?string,
     *   name_source:?string,
     *   name_confidence:?string
     * }
     */
    public function extract(string $text, bool $expectingNameAnswer = false): array
    {
        $original = trim($text);
        if ($original === '') {
            return $this->empty();
        }

        $business = $this->extractBusinessName($original);
        $person = $this->extractExplicitPersonName($original);

        if ($person === null && $expectingNameAnswer) {
            $standalone = $this->standaloneName($original);
            if ($standalone !== null) {
                $person = [
                    'name' => $standalone,
                    'source' => CustomerIdentity::SOURCE_EXPLICIT_USER,
                    'confidence' => CustomerIdentity::CONFIDENCE_HIGH,
                ];
            }
        }

        return [
            'customer_name' => $person['name'] ?? null,
            'business_name' => $business,
            'name_source' => $person['source'] ?? null,
            'name_confidence' => $person['confidence'] ?? null,
        ];
    }

    public function extractPushName(?string $pushName): ?string
    {
        $name = trim((string) $pushName);
        if ($name === '' || self::isPlaceholder($name) || ! $this->isLikelyPersonName($name)) {
            return null;
        }

        return $this->normalizeName($name);
    }

    public static function isPlaceholder(?string $name): bool
    {
        $name = trim((string) $name);
        if ($name === '') {
            return true;
        }
        $hay = mb_strtolower($name);
        $hay = str_replace(['_', '-'], ' ', $hay);
        if (in_array($hay, ['unknown', 'unknown user', 'test user', 'demo account', 'demo user', 'customer', 'user', 'guest', 'lead'], true)) {
            return true;
        }
        if (preg_match('/^(user|customer|lead|test|demo)\s*#?\s*\d+$/u', $hay) === 1) {
            return true;
        }
        if (preg_match('/^(user|customer|test)[_\s-]?\d+$/u', $hay) === 1) {
            return true;
        }

        return false;
    }

    public function isLikelyPersonName(string $name): bool
    {
        $name = $this->normalizeName($name);
        if ($name === '' || self::isPlaceholder($name)) {
            return false;
        }
        if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
            return false;
        }
        if (preg_match('/\d/u', $name) === 1) {
            return false;
        }
        if ($this->containsNonNameCue($name)) {
            return false;
        }
        $tokens = preg_split('/\s+/u', $name) ?: [];
        if (count($tokens) === 0 || count($tokens) > 4) {
            return false;
        }
        foreach ($tokens as $token) {
            if (mb_strlen($token) < 2) {
                return false;
            }
            if (preg_match('/^[\p{L}\p{M}\'\-]+$/u', $token) !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{name:string,source:string,confidence:string}|null
     */
    private function extractExplicitPersonName(string $text): ?array
    {
        $normalized = $this->normalizeForMatch($text);

        $patterns = [
            '/(?:اسمي|اسمى)\s+(?:هو\s+)?(.+?)(?:\s+من\s+|\s+و|$)/u',
            '/(?:أنا|انا)\s+(?:اسمي|اسمى)\s+(.+?)(?:\s+من\s+|\s+و|$)/u',
            '/(?:أنا|انا)\s+(?!من\b|from\b)([^\n،,.]+?)(?:\s+من\s+|\s+و|$)/u',
            '/(?:by the way|بالمناسبة).{0,20}(?:اسمي|اسمى|my name is)\s+(.+)$/u',
            '/my name is\s+(.+?)(?:\s+from\s+|$)/iu',
            '/i(?:\'m| am)\s+(.+?)(?:\s+from\s+|$)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized, $m) !== 1) {
                continue;
            }
            $candidate = $this->trimNameCandidate((string) ($m[1] ?? ''));
            $first = mb_strtolower((preg_split('/\s+/u', $candidate) ?: [''])[0] ?? '');
            if (in_array($first, ['من', 'from', 'عايز', 'عاوز', 'محتاج'], true)) {
                continue;
            }
            if (! $this->isLikelyPersonName($candidate)) {
                continue;
            }

            return [
                'name' => $this->normalizeName($candidate),
                'source' => CustomerIdentity::SOURCE_EXPLICIT_USER,
                'confidence' => CustomerIdentity::CONFIDENCE_HIGH,
            ];
        }

        return null;
    }

    private function extractBusinessName(string $text): ?string
    {
        $normalized = $this->normalizeForMatch($text);
        $patterns = [
            '/من\s+(أتيليه|اتيليه|atelier)\s+(.+)$/u',
            '/(?:الأتيليه|الاتيليه|الأتيليه اسمه|الاتيليه اسمه|atelier(?:\s+name)?(?:\s+is)?)\s+(.+)$/iu',
            '/from\s+(atelier|studio)\s+(.+)$/iu',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized, $m) !== 1) {
                continue;
            }
            if (isset($m[2]) && trim((string) $m[2]) !== '') {
                $label = trim((string) $m[1]);
                $rest = $this->trimBusinessCandidate((string) $m[2]);
                $full = $this->composeBusiness($label, $rest);
            } else {
                $full = $this->trimBusinessCandidate((string) ($m[1] ?? ''));
            }
            if ($this->isLikelyBusinessName($full)) {
                return $this->normalizeName($full);
            }
        }

        return null;
    }

    private function standaloneName(string $text): ?string
    {
        $candidate = $this->trimNameCandidate($text);
        if (! $this->isLikelyPersonName($candidate)) {
            return null;
        }

        return $this->normalizeName($candidate);
    }

    private function composeBusiness(string $label, string $rest): string
    {
        $rest = $this->normalizeName($rest);
        $label = trim($label);
        if ($rest === '') {
            return '';
        }
        if (preg_match('/^(أتيليه|اتيليه|atelier|studio)$/iu', $rest) === 1) {
            return '';
        }
        if (preg_match('/^(أتيليه|اتيليه|atelier|studio)\b/iu', $rest) === 1) {
            return $rest;
        }
        if (preg_match('/^(أتيليه|اتيليه|atelier|studio)$/iu', $label) === 1) {
            return trim($label.' '.$rest);
        }

        return $rest;
    }

    private function isLikelyBusinessName(string $name): bool
    {
        $name = $this->normalizeName($name);
        if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 80) {
            return false;
        }
        if ($this->containsNonNameCue($name) && ! preg_match('/(أتيليه|اتيليه|atelier)/iu', $name)) {
            return false;
        }

        return preg_match('/^[\p{L}\d\s\'\-&]+$/u', $name) === 1;
    }

    private function containsNonNameCue(string $text): bool
    {
        $hay = mb_strtolower($text);
        $cues = [
            'عايز', 'عاوز', 'أريد', 'ابي', 'أبي', 'محتاج', 'عرف', 'أعرف', 'السعر', 'الاسعار',
            'كام', 'كم', 'ازاي', 'إزاي', 'how', 'price', 'pricing', 'want', 'need', 'know',
            'فرع', 'فروع', 'باقة', 'باقه', 'ديمو', 'عرض', 'تجربة', 'اشتراك', 'فاتورة',
            'hello', 'hi ', 'hey', 'مرحبا', 'السلام', 'مساء', 'صباح', 'اهلا', 'أهلا',
        ];
        foreach ($cues as $cue) {
            if ($cue !== '' && mb_strpos($hay, $cue) !== false) {
                return true;
            }
        }

        return false;
    }

    private function trimNameCandidate(string $value): string
    {
        $value = $this->normalizeName($value);
        $value = preg_replace('/\s+(من|from)\s+.+$/u', '', $value) ?? $value;
        $value = preg_replace('/^(أنا|انا|i am|i\'m)\s+/u', '', $value) ?? $value;

        return $this->normalizeName($value);
    }

    private function trimBusinessCandidate(string $value): string
    {
        $value = $this->normalizeName($value);
        $value = preg_replace('/\s+(وعندي|وعندى|and i|and we).+$/u', '', $value) ?? $value;

        return $this->normalizeName($value);
    }

    private function normalizeForMatch(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $text;
    }

    public function normalizeName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
        $name = preg_replace('/^[\s.،,!؟?\-]+|[\s.،,!؟?\-]+$/u', '', $name) ?? $name;

        return $name;
    }

    /**
     * @return array{customer_name:null,business_name:null,name_source:null,name_confidence:null}
     */
    private function empty(): array
    {
        return [
            'customer_name' => null,
            'business_name' => null,
            'name_source' => null,
            'name_confidence' => null,
        ];
    }
}

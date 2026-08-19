<?php

declare(strict_types=1);

namespace App\Services\Platform\AiSales\Identity;

/**
 * Professional demo tenant / admin naming and deterministic demo emails.
 * Does not invent a person or business name.
 */
final class DemoAccountIdentityService
{
    public const EMAIL_DOMAIN = 'demo.dressnmore.com';

    /**
     * @param  callable(string):bool|null  $emailTaken
     * @return array{tenant_name:?string,admin_name:?string,email:?string,usable:bool}
     */
    public function propose(CustomerIdentity $identity, ?callable $emailTaken = null): array
    {
        $person = $identity->trustedCustomerName();
        $business = $this->usableBusiness($identity->businessName);
        $tenantName = $business ?: $person;
        $adminName = $person ?: $business;
        $emailSeed = $person ?: $business;
        $email = $emailSeed !== null ? $this->uniqueEmail($emailSeed, $emailTaken) : null;

        return [
            'tenant_name' => $tenantName,
            'admin_name' => $adminName,
            'email' => $email,
            'usable' => $tenantName !== null && $adminName !== null && $email !== null,
        ];
    }

    /**
     * @param  callable(string):bool|null  $emailTaken
     */
    public function uniqueEmail(string $sourceName, ?callable $emailTaken = null): ?string
    {
        $local = $this->emailLocalPart($sourceName);
        if ($local === null) {
            return null;
        }
        $candidate = $local.'@'.self::EMAIL_DOMAIN;
        if ($emailTaken === null || ! $emailTaken($candidate)) {
            return $candidate;
        }
        for ($i = 2; $i <= 99; $i++) {
            $candidate = $local.$i.'@'.self::EMAIL_DOMAIN;
            if (! $emailTaken($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function emailLocalPart(string $sourceName): ?string
    {
        if (CustomerNameExtractor::isPlaceholder($sourceName)) {
            return null;
        }
        $ascii = $this->transliterate($sourceName);
        $ascii = strtolower($ascii);
        $ascii = preg_replace('/[^a-z0-9]+/i', '.', $ascii) ?? '';
        $ascii = trim($ascii, '.');
        $ascii = preg_replace('/\.{2,}/', '.', $ascii) ?? $ascii;
        if ($ascii === '' || preg_match('/^[0-9.]+$/', $ascii) === 1) {
            return null;
        }
        if (strlen($ascii) > 48) {
            $ascii = rtrim(substr($ascii, 0, 48), '.');
        }
        if (preg_match('/^[a-z][a-z0-9.]*[a-z0-9]$/', $ascii) !== 1 && preg_match('/^[a-z][a-z0-9]*$/', $ascii) !== 1) {
            $ascii = trim($ascii, '.');
            if ($ascii === '' || ! preg_match('/[a-z]/', $ascii)) {
                return null;
            }
        }

        return $ascii;
    }

    public function transliterate(string $value): string
    {
        $parts = preg_split('/\s+/u', trim($value)) ?: [];
        $mapped = [];
        foreach ($parts as $part) {
            $mapped[] = $this->transliterateToken($part);
        }

        return trim(implode(' ', array_filter($mapped)));
    }

    private function transliterateToken(string $token): string
    {
        $aliases = [
            'أحمد' => 'ahmed',
            'احمد' => 'ahmed',
            'محمد' => 'mohamed',
            'محمود' => 'mahmoud',
            'علي' => 'ali',
            'حسن' => 'hassan',
            'حسين' => 'hussein',
            'فاطمة' => 'fatima',
            'سارة' => 'sara',
            'نورا' => 'noura',
            'نور' => 'nour',
            'منى' => 'mona',
            'أتيليه' => 'atelier',
            'اتيليه' => 'atelier',
        ];
        if (isset($aliases[$token])) {
            return $aliases[$token];
        }
        $lower = mb_strtolower($token);
        foreach ($aliases as $ar => $en) {
            if (mb_strtolower($ar) === $lower) {
                return $en;
            }
        }

        $map = [
            'أ' => 'a', 'إ' => 'i', 'آ' => 'a', 'ا' => 'a', 'ى' => 'a', 'ي' => 'y', 'ئ' => 'y',
            'ب' => 'b', 'ت' => 't', 'ث' => 'th', 'ج' => 'j', 'ح' => 'h', 'خ' => 'kh',
            'د' => 'd', 'ذ' => 'dh', 'ر' => 'r', 'ز' => 'z', 'س' => 's', 'ش' => 'sh',
            'ص' => 's', 'ض' => 'd', 'ط' => 't', 'ظ' => 'z', 'ع' => 'a', 'غ' => 'gh',
            'ف' => 'f', 'ق' => 'q', 'ك' => 'k', 'ل' => 'l', 'م' => 'm', 'ن' => 'n',
            'ه' => 'h', 'و' => 'o', 'ؤ' => 'w', 'ة' => 'a', 'ء' => '', 'ﻻ' => 'la',
            'ڤ' => 'v', 'پ' => 'p', 'چ' => 'ch',
        ];
        $out = '';
        $chars = preg_split('//u', $token, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($chars as $char) {
            $out .= $map[$char] ?? $char;
        }

        return $out;
    }

    private function usableBusiness(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '' || CustomerNameExtractor::isPlaceholder($name)) {
            return null;
        }

        return $name;
    }
}

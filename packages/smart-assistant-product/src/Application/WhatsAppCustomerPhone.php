<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

/**
 * Turns a WhatsApp sender JID into a storeable customer phone.
 * `@lid` IDs are not phone numbers — never persist them as phone/whatsapp.
 */
final class WhatsAppCustomerPhone
{
    public static function msisdn(?string $fromPhone, string $fromJid): ?string
    {
        foreach ([$fromPhone, $fromJid] as $raw) {
            $raw = trim((string) $raw);
            if ($raw === '') {
                continue;
            }
            $lower = strtolower($raw);
            if (str_contains($lower, '@lid') || str_contains($lower, '@g.us') || str_contains($lower, '@broadcast')) {
                continue;
            }

            $local = explode('@', $raw)[0];
            $local = explode(':', $local)[0];
            $digits = preg_replace('/\D+/', '', $local) ?? '';
            $len = strlen($digits);
            if ($len >= 8 && $len <= 15) {
                return $digits;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function lookupKeys(?string $fromPhone, string $fromJid): array
    {
        $keys = [];
        $msisdn = self::msisdn($fromPhone, $fromJid);
        if ($msisdn !== null) {
            $keys[] = $msisdn;
            if (str_starts_with($msisdn, '20') && strlen($msisdn) >= 11) {
                $keys[] = '0'.substr($msisdn, 2);
            }
            if (str_starts_with($msisdn, '0') && strlen($msisdn) >= 10) {
                $keys[] = '20'.substr($msisdn, 1);
            }
        }
        $jid = trim($fromJid);
        if ($jid !== '') {
            $keys[] = $jid;
        }

        return array_values(array_unique(array_filter($keys, static fn (string $v): bool => $v !== '')));
    }
}

<?php

declare(strict_types=1);

namespace App\Support\AiSales;

/**
 * Channel-agnostic sales channels. Adapters land in a later sprint.
 */
enum AiSalesChannel: string
{
    case WhatsApp = 'whatsapp';
    case Instagram = 'instagram';
    case Facebook = 'facebook';
    case Website = 'website';

    public static function fromStored(?string $value): self
    {
        $raw = strtolower(trim((string) $value));

        return self::tryFrom($raw) ?? match ($raw) {
            'facebook_messenger', 'messenger' => self::Facebook,
            'web', 'web_chat', 'site' => self::Website,
            'ig', 'instagram_direct' => self::Instagram,
            'wa' => self::WhatsApp,
            default => self::Website,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}

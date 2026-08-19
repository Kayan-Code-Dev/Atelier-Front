<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Channel;

/**
 * Official channel type catalog (no adapters in Sprint 21).
 */
final class ChannelTypeCatalog
{
    public const DASHBOARD = 'dashboard';
    public const WHATSAPP = 'whatsapp';
    public const FACEBOOK = 'facebook';
    public const INSTAGRAM = 'instagram';
    public const MESSENGER = 'messenger';
    public const TELEGRAM = 'telegram';
    public const WEBSITE_WIDGET = 'website_widget';
    public const EMAIL = 'email';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::DASHBOARD,
            self::WHATSAPP,
            self::FACEBOOK,
            self::INSTAGRAM,
            self::MESSENGER,
            self::TELEGRAM,
            self::WEBSITE_WIDGET,
            self::EMAIL,
        ];
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Identity;

/**
 * Supported inbound channel types (Telegram reserved as future).
 */
enum ChannelType: string
{
    case WhatsApp = 'whatsapp';
    case Messenger = 'messenger';
    case Instagram = 'instagram';
    case Facebook = 'facebook';
    case Email = 'email';
    case WebChat = 'web_chat';
    case MobileApp = 'mobile_app';
    case Telegram = 'telegram';

    public function isSupported(): bool
    {
        return $this !== self::Telegram;
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Channel;

enum ChannelType: string
{
    case WhatsApp = 'whatsapp';
    case FacebookMessenger = 'facebook_messenger';
    case InstagramDirect = 'instagram_direct';
    case FacebookComments = 'facebook_comments';
    case InstagramComments = 'instagram_comments';
    case Telegram = 'telegram';
    case WebChat = 'web_chat';
    case MobileAppChat = 'mobile_app_chat';
    case Email = 'email';
    case Future = 'future_channel';
}

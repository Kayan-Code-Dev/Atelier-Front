<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

use DressnMore\SmartAssistant\Contracts\Channel\ChannelConnectorInterface;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use DressnMore\SmartAssistantProduct\Infrastructure\Channel\FacebookChannelConnector;
use DressnMore\SmartAssistantProduct\Infrastructure\Channel\InstagramChannelConnector;
use DressnMore\SmartAssistantProduct\Infrastructure\Channel\WhatsAppChannelConnector;
use DressnMore\SmartAssistantProduct\Infrastructure\WhatsAppWeb\WhatsAppWebChannelConnector;
use InvalidArgumentException;

final class ChannelConnectorManager
{
    public function __construct(
        private readonly WhatsAppChannelConnector $whatsapp,
        private readonly WhatsAppWebChannelConnector $whatsappWeb,
        private readonly FacebookChannelConnector $facebook,
        private readonly InstagramChannelConnector $instagram,
    ) {}

    public function for(string $channelType): ChannelConnectorInterface
    {
        $whatsapp = (bool) config('smart-assistant-product.whatsapp_web.enabled', false)
            ? $this->whatsappWeb
            : $this->whatsapp;

        return match ($channelType) {
            SocialChannelCatalog::WHATSAPP => $whatsapp,
            SocialChannelCatalog::FACEBOOK => $this->facebook,
            SocialChannelCatalog::INSTAGRAM => $this->instagram,
            default => throw new InvalidArgumentException("Unsupported channel: {$channelType}"),
        };
    }

    /**
     * @return array<string, ChannelConnectorInterface>
     */
    public function all(): array
    {
        return [
            SocialChannelCatalog::WHATSAPP => $this->whatsapp,
            SocialChannelCatalog::FACEBOOK => $this->facebook,
            SocialChannelCatalog::INSTAGRAM => $this->instagram,
        ];
    }
}

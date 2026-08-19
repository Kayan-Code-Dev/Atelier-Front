<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Infrastructure\WhatsAppWeb;

use DressnMore\SmartAssistant\Contracts\Channel\ChannelConnectorInterface;
use DressnMore\SmartAssistantProduct\Domain\ChannelConnectionStoreInterface;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use RuntimeException;

/**
 * WhatsApp Web (QR pairing) connector — talks to the local Baileys gateway.
 */
final class WhatsAppWebChannelConnector implements ChannelConnectorInterface
{
    public function __construct(
        private readonly ChannelConnectionStoreInterface $store,
        private readonly WhatsAppGatewayClient $gateway,
    ) {}

    public function channelType(): string
    {
        return SocialChannelCatalog::WHATSAPP;
    }

    public function connect(string $tenantId, array $config = []): void
    {
        $state = $this->gateway->createSession($tenantId);
        $session = $state['session'] ?? [];

        $this->store->connect($tenantId, $this->channelType(), [
            'session_id' => 'tenant_'.$tenantId,
            'provider' => 'whatsapp_web',
            'display_name' => $session['display_name'] ?? null,
            'phone_number' => $session['phone'] ?? null,
            'auto_reply_enabled' => (bool) ($config['auto_reply_enabled'] ?? true),
            'auto_reply_mode' => (string) ($config['auto_reply_mode'] ?? config('smart-assistant-product.whatsapp.auto_reply_mode', 'sales')),
        ]);
    }

    public function disconnect(string $tenantId): void
    {
        try {
            $this->gateway->disconnect($tenantId);
        } catch (RuntimeException) {
            // gateway already gone — still mark locally disconnected
        }
        $this->store->disconnect($tenantId, $this->channelType());
    }

    public function receiveMessage(string $tenantId, array $payload): array
    {
        $normalized = [
            'id' => (string) ($payload['message_id'] ?? uniqid('wa_web_', true)),
            'channel' => $this->channelType(),
            'direction' => 'inbound',
            'from' => (string) ($payload['from'] ?? $payload['from_phone'] ?? 'unknown'),
            'from_phone' => (string) ($payload['from_phone'] ?? ''),
            'text' => (string) ($payload['text'] ?? ''),
            'type' => (string) ($payload['type'] ?? 'text'),
            'received_at' => (string) ($payload['timestamp'] ?? now()->toIso8601String()),
            'raw' => $payload,
        ];
        $this->store->pushInbox($tenantId, $this->channelType(), $normalized);

        return $normalized;
    }

    public function sendMessage(string $tenantId, array $message): void
    {
        $to = (string) ($message['to'] ?? '');
        $text = (string) ($message['text'] ?? '');
        if ($to === '' || $text === '') {
            throw new RuntimeException('to و text مطلوبان لإرسال رسالة واتساب');
        }

        $result = $this->gateway->send((string) ($message['session_key'] ?? $tenantId), $to, $text);

        $this->store->pushOutbox($tenantId, $this->channelType(), [
            'id' => (string) ($result['result']['id'] ?? uniqid('wa_web_out_', true)),
            'channel' => $this->channelType(),
            'direction' => 'outbound',
            'to' => $to,
            'text' => $text,
            'sent_at' => now()->toIso8601String(),
            'status' => 'sent',
            'provider' => 'whatsapp_web',
            'raw' => $result,
        ]);
    }

    public function receiveComment(string $tenantId, array $payload): array
    {
        return [
            'id' => '',
            'channel' => $this->channelType(),
            'supported' => false,
            'message' => 'Comments are not supported on WhatsApp',
        ];
    }

    public function replyComment(string $tenantId, array $reply): void
    {
        throw new RuntimeException('Comments are not supported on WhatsApp');
    }

    public function uploadMedia(string $tenantId, array $media): string
    {
        throw new RuntimeException('Media upload not implemented yet');
    }

    public function downloadMedia(string $tenantId, string $mediaId): string
    {
        throw new RuntimeException('Media download not implemented yet');
    }

    public function verifyWebhook(string $tenantId, array $headers, string $body): bool
    {
        return true;
    }

    public function syncStatus(string $tenantId): string
    {
        try {
            $state = $this->gateway->status($tenantId);

            return (string) (($state['session']['status'] ?? 'disconnected') === 'connected' ? 'connected' : 'disconnected');
        } catch (RuntimeException) {
            return 'disconnected';
        }
    }
}

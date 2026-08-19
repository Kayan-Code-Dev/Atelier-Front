<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Infrastructure\Channel;

use DressnMore\SmartAssistant\Contracts\Channel\ChannelConnectorInterface;
use DressnMore\SmartAssistantProduct\Application\WhatsAppProfileSync;
use DressnMore\SmartAssistantProduct\Domain\ChannelConnectionStoreInterface;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaWhatsAppCloudClient;
use RuntimeException;

/**
 * Live WhatsApp Cloud API connector.
 */
final class WhatsAppChannelConnector implements ChannelConnectorInterface
{
    public function __construct(
        private readonly ChannelConnectionStoreInterface $store,
        private readonly MetaWhatsAppCloudClient $client,
        private readonly WhatsAppProfileSync $profileSync,
    ) {}

    public function channelType(): string
    {
        return SocialChannelCatalog::WHATSAPP;
    }

    public function connect(string $tenantId, array $config = []): void
    {
        $phoneNumberId = trim((string) ($config['phone_number_id'] ?? ''));
        $accessToken = trim((string) ($config['access_token'] ?? ''));
        if ($phoneNumberId === '' || $accessToken === '') {
            throw new RuntimeException('phone_number_id و access_token مطلوبان لربط واتساب');
        }

        $config = $this->profileSync->enrichConnectConfig($config);
        $this->store->connect($tenantId, $this->channelType(), $config);
    }

    public function disconnect(string $tenantId): void
    {
        $this->store->disconnect($tenantId, $this->channelType());
    }

    public function receiveMessage(string $tenantId, array $payload): array
    {
        $this->assertConnected($tenantId);
        $normalized = [
            'id' => (string) ($payload['id'] ?? uniqid('wa_', true)),
            'channel' => $this->channelType(),
            'direction' => 'inbound',
            'from' => (string) ($payload['from'] ?? 'unknown'),
            'text' => (string) ($payload['text'] ?? $payload['message'] ?? ''),
            'type' => (string) ($payload['type'] ?? 'text'),
            'received_at' => now()->toIso8601String(),
            'raw' => $payload['raw'] ?? $payload,
        ];
        $this->store->pushInbox($tenantId, $this->channelType(), $normalized);

        return $normalized;
    }

    public function sendMessage(string $tenantId, array $message): void
    {
        $creds = $this->store->credentials($tenantId, $this->channelType());
        if ($creds === null) {
            throw new RuntimeException('واتساب غير مربوط أو التوكن ناقص');
        }

        $to = (string) ($message['to'] ?? '');
        $text = (string) ($message['text'] ?? '');
        if ($to === '' || $text === '') {
            throw new RuntimeException('to و text مطلوبان لإرسال رسالة واتساب');
        }

        $result = $this->client->sendText(
            (string) $creds['phone_number_id'],
            (string) $creds['access_token'],
            $to,
            $text
        );

        $this->store->pushOutbox($tenantId, $this->channelType(), [
            'id' => (string) ($result['messages'][0]['id'] ?? uniqid('wa_out_', true)),
            'channel' => $this->channelType(),
            'direction' => 'outbound',
            'to' => $to,
            'text' => $text,
            'sent_at' => now()->toIso8601String(),
            'status' => 'sent',
            'provider' => 'meta_cloud',
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
        return $this->store->isConnected($tenantId, $this->channelType())
            ? 'connected'
            : 'disconnected';
    }

    private function assertConnected(string $tenantId): void
    {
        if (! $this->store->isConnected($tenantId, $this->channelType())) {
            throw new RuntimeException('WhatsApp is not connected for tenant '.$tenantId);
        }
    }
}

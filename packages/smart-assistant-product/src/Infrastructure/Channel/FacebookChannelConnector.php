<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Infrastructure\Channel;

use DressnMore\SmartAssistant\Contracts\Channel\ChannelConnectorInterface;
use DressnMore\SmartAssistantProduct\Domain\ChannelConnectionStoreInterface;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaMessengerGraphClient;
use RuntimeException;

final class FacebookChannelConnector implements ChannelConnectorInterface
{
    public function __construct(
        private readonly ChannelConnectionStoreInterface $store,
        private readonly MetaMessengerGraphClient $client,
    ) {}

    public function channelType(): string
    {
        return SocialChannelCatalog::FACEBOOK;
    }

    public function connect(string $tenantId, array $config = []): void
    {
        $pageId = trim((string) ($config['page_id'] ?? $config['external_account_id'] ?? ''));
        $accessToken = trim((string) ($config['access_token'] ?? ''));
        if ($pageId === '' || $accessToken === '') {
            throw new RuntimeException('page_id و access_token مطلوبان لربط فيسبوك');
        }
        $config['page_id'] = $pageId;
        $config['external_account_id'] = $pageId;
        $config['auto_reply_enabled'] = array_key_exists('auto_reply_enabled', $config)
            ? (bool) $config['auto_reply_enabled']
            : true;
        $config['auto_reply_mode'] = $config['auto_reply_mode'] ?? 'template';
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
            'id' => (string) ($payload['id'] ?? uniqid('fb_msg_', true)),
            'channel' => $this->channelType(),
            'direction' => 'inbound',
            'from' => (string) ($payload['from'] ?? 'unknown'),
            'text' => (string) ($payload['text'] ?? ''),
            'type' => (string) ($payload['type'] ?? 'text'),
            'received_at' => now()->toIso8601String(),
            'raw' => $payload['raw'] ?? $payload,
        ];
        $this->store->pushInbox($tenantId, $this->channelType(), $normalized);

        return $normalized;
    }

    public function sendMessage(string $tenantId, array $message): void
    {
        $creds = $this->requireCreds($tenantId);
        $to = (string) ($message['to'] ?? '');
        $text = (string) ($message['text'] ?? '');
        if ($to === '' || $text === '') {
            throw new RuntimeException('to و text مطلوبان لإرسال رسالة فيسبوك');
        }

        $result = $this->client->sendText(
            (string) $creds['page_id'],
            (string) $creds['access_token'],
            $to,
            $text
        );

        $this->store->pushOutbox($tenantId, $this->channelType(), [
            'id' => (string) ($result['message_id'] ?? uniqid('fb_out_', true)),
            'channel' => $this->channelType(),
            'direction' => 'outbound',
            'to' => $to,
            'text' => $text,
            'sent_at' => now()->toIso8601String(),
            'status' => 'sent',
            'provider' => 'meta_graph',
            'raw' => $result,
        ]);
    }

    public function receiveComment(string $tenantId, array $payload): array
    {
        $this->assertConnected($tenantId);
        $normalized = [
            'id' => (string) ($payload['id'] ?? uniqid('fb_cmt_', true)),
            'channel' => $this->channelType(),
            'post_id' => (string) ($payload['post_id'] ?? ''),
            'from' => (string) ($payload['from'] ?? 'unknown'),
            'text' => (string) ($payload['text'] ?? ''),
            'received_at' => now()->toIso8601String(),
            'raw' => $payload['raw'] ?? $payload,
        ];
        $this->store->pushComment($tenantId, $this->channelType(), $normalized);

        return $normalized;
    }

    public function replyComment(string $tenantId, array $reply): void
    {
        $creds = $this->requireCreds($tenantId);
        $commentId = (string) ($reply['comment_id'] ?? '');
        $text = (string) ($reply['text'] ?? '');
        if ($commentId === '' || $text === '') {
            throw new RuntimeException('comment_id و text مطلوبان للرد على تعليق فيسبوك');
        }

        $result = $this->client->replyToComment($commentId, (string) $creds['access_token'], $text);
        $this->store->pushOutbox($tenantId, $this->channelType(), [
            'id' => (string) ($result['id'] ?? uniqid('fb_cmt_reply_', true)),
            'type' => 'comment_reply',
            'channel' => $this->channelType(),
            'comment_id' => $commentId,
            'text' => $text,
            'sent_at' => now()->toIso8601String(),
            'status' => 'sent',
            'provider' => 'meta_graph',
            'raw' => $result,
        ]);
    }

    public function uploadMedia(string $tenantId, array $media): string
    {
        throw new RuntimeException('Media upload not implemented yet for Facebook');
    }

    public function downloadMedia(string $tenantId, string $mediaId): string
    {
        throw new RuntimeException('Media download not implemented yet for Facebook');
    }

    public function verifyWebhook(string $tenantId, array $headers, string $body): bool
    {
        return true;
    }

    public function syncStatus(string $tenantId): string
    {
        return $this->store->isConnected($tenantId, $this->channelType()) ? 'connected' : 'disconnected';
    }

    /**
     * @return array<string, mixed>
     */
    private function requireCreds(string $tenantId): array
    {
        $creds = $this->store->credentials($tenantId, $this->channelType());
        if ($creds === null || empty($creds['access_token']) || empty($creds['page_id'])) {
            throw new RuntimeException('فيسبوك غير مربوط أو التوكن ناقص');
        }

        return $creds;
    }

    private function assertConnected(string $tenantId): void
    {
        if (! $this->store->isConnected($tenantId, $this->channelType())) {
            throw new RuntimeException('Facebook is not connected for tenant '.$tenantId);
        }
    }
}

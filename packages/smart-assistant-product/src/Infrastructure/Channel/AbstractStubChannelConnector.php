<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Infrastructure\Channel;

use DressnMore\SmartAssistant\Contracts\Channel\ChannelConnectorInterface;
use DressnMore\SmartAssistantProduct\Domain\ChannelConnectionStoreInterface;
use RuntimeException;

/**
 * Shared stub connector — records messages/comments in memory; no live Meta API yet.
 */
abstract class AbstractStubChannelConnector implements ChannelConnectorInterface
{
    public function __construct(
        protected readonly ChannelConnectionStoreInterface $store,
    ) {}

    abstract public function channelType(): string;

    public function connect(string $tenantId, array $config = []): void
    {
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
            'id' => (string) ($payload['id'] ?? uniqid('msg_', true)),
            'channel' => $this->channelType(),
            'direction' => 'inbound',
            'from' => (string) ($payload['from'] ?? 'unknown'),
            'text' => (string) ($payload['text'] ?? $payload['message'] ?? ''),
            'received_at' => now()->toIso8601String(),
            'raw' => $payload,
        ];
        $this->store->pushInbox($tenantId, $this->channelType(), $normalized);

        return $normalized;
    }

    public function sendMessage(string $tenantId, array $message): void
    {
        $this->assertConnected($tenantId);
        $this->store->pushOutbox($tenantId, $this->channelType(), [
            'id' => (string) ($message['id'] ?? uniqid('out_', true)),
            'channel' => $this->channelType(),
            'direction' => 'outbound',
            'to' => (string) ($message['to'] ?? ''),
            'text' => (string) ($message['text'] ?? ''),
            'sent_at' => now()->toIso8601String(),
            'status' => 'queued_stub',
        ]);
    }

    public function receiveComment(string $tenantId, array $payload): array
    {
        $this->assertConnected($tenantId);
        $normalized = [
            'id' => (string) ($payload['id'] ?? uniqid('cmt_', true)),
            'channel' => $this->channelType(),
            'post_id' => (string) ($payload['post_id'] ?? ''),
            'from' => (string) ($payload['from'] ?? 'unknown'),
            'text' => (string) ($payload['text'] ?? ''),
            'received_at' => now()->toIso8601String(),
            'raw' => $payload,
        ];
        $this->store->pushComment($tenantId, $this->channelType(), $normalized);

        return $normalized;
    }

    public function replyComment(string $tenantId, array $reply): void
    {
        $this->assertConnected($tenantId);
        $this->store->pushOutbox($tenantId, $this->channelType(), [
            'id' => (string) ($reply['id'] ?? uniqid('cmt_reply_', true)),
            'type' => 'comment_reply',
            'channel' => $this->channelType(),
            'comment_id' => (string) ($reply['comment_id'] ?? ''),
            'text' => (string) ($reply['text'] ?? ''),
            'sent_at' => now()->toIso8601String(),
            'status' => 'queued_stub',
        ]);
    }

    public function uploadMedia(string $tenantId, array $media): string
    {
        $this->assertConnected($tenantId);

        return 'media_stub_'.uniqid('', true);
    }

    public function downloadMedia(string $tenantId, string $mediaId): string
    {
        $this->assertConnected($tenantId);

        return '';
    }

    public function verifyWebhook(string $tenantId, array $headers, string $body): bool
    {
        // Stub accepts Meta-style hub.challenge verification when connected or verifying setup.
        return true;
    }

    public function syncStatus(string $tenantId): string
    {
        return $this->store->isConnected($tenantId, $this->channelType())
            ? 'connected'
            : 'disconnected';
    }

    protected function assertConnected(string $tenantId): void
    {
        if (! $this->store->isConnected($tenantId, $this->channelType())) {
            throw new RuntimeException(sprintf(
                'Channel %s is not connected for tenant %s',
                $this->channelType(),
                $tenantId
            ));
        }
    }
}

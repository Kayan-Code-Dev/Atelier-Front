<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Channel;

/**
 * Unified channel connector — all channels implement this surface.
 * No adapters implemented in Sprint 21.
 */
interface ChannelConnectorInterface
{
    public function connect(string $tenantId, array $config = []): void;

    public function disconnect(string $tenantId): void;

    /**
     * @return array<string, mixed>
     */
    public function receiveMessage(string $tenantId, array $payload): array;

    /**
     * @param array<string, mixed> $message
     */
    public function sendMessage(string $tenantId, array $message): void;

    /**
     * @return array<string, mixed>
     */
    public function receiveComment(string $tenantId, array $payload): array;

    /**
     * @param array<string, mixed> $reply
     */
    public function replyComment(string $tenantId, array $reply): void;

    /**
     * @param array<string, mixed> $media
     */
    public function uploadMedia(string $tenantId, array $media): string;

    public function downloadMedia(string $tenantId, string $mediaId): string;

    public function verifyWebhook(string $tenantId, array $headers, string $body): bool;

    public function syncStatus(string $tenantId): string;
}

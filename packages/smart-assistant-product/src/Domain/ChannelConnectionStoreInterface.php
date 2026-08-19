<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Domain;

interface ChannelConnectionStoreInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function connect(string $tenantId, string $channelType, array $config = []): void;

    public function disconnect(string $tenantId, string $channelType): void;

    /**
     * Update connection settings (auto-reply, etc.) without reconnecting.
     *
     * @param array<string, mixed> $settings
     */
    public function updateSettings(string $tenantId, string $channelType, array $settings): void;

    public function isConnected(string $tenantId, string $channelType): bool;

    /**
     * @return array<string, mixed>
     */
    public function state(string $tenantId, string $channelType): array;

    /**
     * Full credentials for outbound API (includes decrypted token). Never expose to FE.
     *
     * @return array<string, mixed>|null
     */
    public function credentials(string $tenantId, string $channelType): ?array;

    public function findTenantIdByExternalAccount(string $channelType, string $externalAccountId): ?string;

    /**
     * @param array<string, mixed> $message
     */
    public function pushInbox(string $tenantId, string $channelType, array $message): void;

    /**
     * @param array<string, mixed> $comment
     */
    public function pushComment(string $tenantId, string $channelType, array $comment): void;

    /**
     * @param array<string, mixed> $message
     */
    public function pushOutbox(string $tenantId, string $channelType, array $message): void;

    /**
     * @return list<array<string, mixed>>
     */
    public function inbox(string $tenantId, string $channelType): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function comments(string $tenantId, string $channelType): array;

    /**
     * Persist non-secret WhatsApp/Meta profile fields for FE display.
     *
     * @param array{display_phone_number?:string, verified_name?:string, display_name?:string, quality_rating?:string} $profile
     */
    public function rememberPublicProfile(string $tenantId, string $channelType, array $profile): void;
}

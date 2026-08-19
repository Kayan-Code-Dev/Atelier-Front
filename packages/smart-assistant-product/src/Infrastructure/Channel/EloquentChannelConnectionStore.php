<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Infrastructure\Channel;

use DressnMore\SmartAssistantProduct\Domain\ChannelConnectionStoreInterface;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantChannelConnection;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantInboundMessage;

/**
 * Persists WhatsApp (and later FB/IG) credentials in central DB.
 * Keeps a small in-process inbox for recent FE polling; durable log is inbound_messages.
 */
final class EloquentChannelConnectionStore implements ChannelConnectionStoreInterface
{
    /** @var array<string, array<string, array<string, mixed>>> */
    private array $runtime = [];

    public function connect(string $tenantId, string $channelType, array $config = []): void
    {
        $external = (string) ($config['phone_number_id'] ?? $config['external_account_id'] ?? $config['page_id'] ?? '');
        $row = SmartAssistantChannelConnection::query()->updateOrCreate(
            [
                'tenant_id' => (int) $tenantId,
                'channel_type' => $channelType,
            ],
            [
                'external_account_id' => $external !== '' ? $external : null,
                'waba_id' => $config['waba_id'] ?? null,
                'display_name' => $config['display_name'] ?? $config['verified_name'] ?? $config['display_phone'] ?? null,
                'access_token' => $config['access_token'] ?? null,
                'webhook_verify_token' => $config['webhook_verify_token'] ?? null,
                'app_secret' => $config['app_secret'] ?? null,
                'status' => 'connected',
                'auto_reply_enabled' => (bool) ($config['auto_reply_enabled'] ?? ($channelType === SocialChannelCatalog::WHATSAPP)),
                'auto_reply_mode' => (string) ($config['auto_reply_mode'] ?? config('smart-assistant-product.whatsapp.auto_reply_mode', 'template')),
                'meta' => $this->publicMeta($config),
                'connected_at' => now(),
                'last_sync_at' => now(),
            ]
        );

        $this->runtime[$tenantId][$channelType] = $this->mapRow($row);
    }

    public function disconnect(string $tenantId, string $channelType): void
    {
        $row = SmartAssistantChannelConnection::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('channel_type', $channelType)
            ->first();

        if ($row !== null) {
            $row->status = 'disconnected';
            $row->access_token = null;
            $row->app_secret = null;
            $row->external_account_id = null;
            $row->auto_reply_enabled = false;
            $row->save();
        }

        if (isset($this->runtime[$tenantId][$channelType])) {
            $this->runtime[$tenantId][$channelType]['status'] = 'disconnected';
            $this->runtime[$tenantId][$channelType]['auto_reply_enabled'] = false;
            $this->runtime[$tenantId][$channelType]['external_account_id'] = null;
        }
    }

    public function updateSettings(string $tenantId, string $channelType, array $settings): void
    {
        $row = SmartAssistantChannelConnection::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('channel_type', $channelType)
            ->first();

        if ($row === null) {
            return;
        }

        if (array_key_exists('auto_reply_enabled', $settings)) {
            $row->auto_reply_enabled = (bool) $settings['auto_reply_enabled'];
        }
        if (array_key_exists('auto_reply_mode', $settings) && is_string($settings['auto_reply_mode'])) {
            $row->auto_reply_mode = $settings['auto_reply_mode'];
        }
        if (array_key_exists('display_name', $settings)) {
            $row->display_name = $settings['display_name'] !== null
                ? (string) $settings['display_name']
                : null;
        }
        $row->save();

        unset($this->runtime[$tenantId][$channelType]);
    }

    public function isConnected(string $tenantId, string $channelType): bool
    {
        return ($this->state($tenantId, $channelType)['status'] ?? null) === 'connected';
    }

    public function state(string $tenantId, string $channelType): array
    {
        if (isset($this->runtime[$tenantId][$channelType])) {
            return $this->runtime[$tenantId][$channelType];
        }

        $row = SmartAssistantChannelConnection::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('channel_type', $channelType)
            ->orderByRaw("CASE WHEN status = 'connected' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        if ($row === null) {
            return [
                'status' => 'disconnected',
                'connected_at' => null,
                'config' => [],
                'last_sync_at' => null,
                'inbox' => [],
                'comments' => [],
                'outbox' => [],
                'live_api' => $channelType === SocialChannelCatalog::WHATSAPP,
                'auto_reply_enabled' => false,
                'auto_reply_mode' => 'off',
                'external_account_id' => null,
            ];
        }

        $mapped = $this->mapRow($row);
        $this->runtime[$tenantId][$channelType] = $mapped;

        return $mapped;
    }

    public function credentials(string $tenantId, string $channelType): ?array
    {
        $row = SmartAssistantChannelConnection::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('channel_type', $channelType)
            ->where('status', 'connected')
            ->first();

        if ($row === null || empty($row->access_token) || empty($row->external_account_id)) {
            return null;
        }

        $meta = is_array($row->meta) ? $row->meta : [];

        return [
            'phone_number_id' => (string) $row->external_account_id,
            'page_id' => (string) ($meta['page_id'] ?? $row->external_account_id),
            'ig_user_id' => (string) ($meta['ig_user_id'] ?? $row->external_account_id),
            'access_token' => (string) $row->access_token,
            'webhook_verify_token' => $row->webhook_verify_token,
            'app_secret' => $row->app_secret,
            'waba_id' => $row->waba_id,
            'auto_reply_enabled' => (bool) $row->auto_reply_enabled,
            'auto_reply_mode' => (string) $row->auto_reply_mode,
        ];
    }

    public function findTenantIdByExternalAccount(string $channelType, string $externalAccountId): ?string
    {
        $row = SmartAssistantChannelConnection::query()
            ->where('channel_type', $channelType)
            ->where('external_account_id', $externalAccountId)
            ->where('status', 'connected')
            ->first();

        return $row !== null ? (string) $row->tenant_id : null;
    }

    public function pushInbox(string $tenantId, string $channelType, array $message): void
    {
        $this->ensureRuntime($tenantId, $channelType);
        $this->runtime[$tenantId][$channelType]['inbox'][] = $message;

        $externalId = (string) ($message['id'] ?? '');
        if ($externalId === '') {
            return;
        }

        SmartAssistantInboundMessage::query()->firstOrCreate(
            [
                'channel_type' => $channelType,
                'external_message_id' => $externalId,
            ],
            [
                'tenant_id' => (int) $tenantId,
                'from_id' => (string) ($message['from'] ?? ''),
                'text' => (string) ($message['text'] ?? ''),
                'status' => 'received',
                'payload' => $message,
            ]
        );
    }

    public function pushComment(string $tenantId, string $channelType, array $comment): void
    {
        $this->ensureRuntime($tenantId, $channelType);
        $this->runtime[$tenantId][$channelType]['comments'][] = $comment;
    }

    public function pushOutbox(string $tenantId, string $channelType, array $message): void
    {
        $this->ensureRuntime($tenantId, $channelType);
        $this->runtime[$tenantId][$channelType]['outbox'][] = $message;
    }

    public function inbox(string $tenantId, string $channelType): array
    {
        $runtime = $this->state($tenantId, $channelType)['inbox'] ?? [];
        if ($runtime !== []) {
            return array_values($runtime);
        }

        return SmartAssistantInboundMessage::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('channel_type', $channelType)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(static fn (SmartAssistantInboundMessage $m): array => [
                'id' => $m->external_message_id,
                'channel' => $m->channel_type,
                'direction' => 'inbound',
                'from' => $m->from_id,
                'text' => $m->text,
                'received_at' => $m->created_at?->toIso8601String(),
                'status' => $m->status,
            ])
            ->all();
    }

    public function comments(string $tenantId, string $channelType): array
    {
        return array_values($this->state($tenantId, $channelType)['comments'] ?? []);
    }

    public function rememberPublicProfile(string $tenantId, string $channelType, array $profile): void
    {
        $row = SmartAssistantChannelConnection::query()
            ->where('tenant_id', (int) $tenantId)
            ->where('channel_type', $channelType)
            ->first();

        if ($row === null) {
            return;
        }

        $meta = is_array($row->meta) ? $row->meta : [];
        if (isset($profile['display_phone_number']) && $profile['display_phone_number'] !== '') {
            $meta['display_phone_number'] = (string) $profile['display_phone_number'];
        }
        if (isset($profile['quality_rating']) && $profile['quality_rating'] !== '') {
            $meta['quality_rating'] = (string) $profile['quality_rating'];
        }
        $row->meta = $meta;

        $name = $profile['display_name'] ?? $profile['verified_name'] ?? null;
        if (is_string($name) && $name !== '') {
            $row->display_name = $name;
        }
        $row->last_sync_at = now();
        $row->save();

        unset($this->runtime[$tenantId][$channelType]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRow(SmartAssistantChannelConnection $row): array
    {
        $meta = is_array($row->meta) ? $row->meta : [];

        return [
            'status' => $row->status,
            'connected_at' => $row->connected_at?->toIso8601String(),
            'last_sync_at' => $row->last_sync_at?->toIso8601String(),
            'config' => [
                'phone_number_id' => $row->external_account_id,
                'display_name' => $row->display_name,
                'display_phone_number' => $meta['display_phone_number'] ?? $meta['phone_number'] ?? null,
                'quality_rating' => $meta['quality_rating'] ?? null,
                'has_token' => filled($row->access_token),
            ],
            'inbox' => $this->runtime[(string) $row->tenant_id][$row->channel_type]['inbox'] ?? [],
            'comments' => $this->runtime[(string) $row->tenant_id][$row->channel_type]['comments'] ?? [],
            'outbox' => $this->runtime[(string) $row->tenant_id][$row->channel_type]['outbox'] ?? [],
            'live_api' => filled($row->access_token),
            'auto_reply_enabled' => (bool) $row->auto_reply_enabled,
            'auto_reply_mode' => (string) $row->auto_reply_mode,
            'external_account_id' => $row->external_account_id,
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function publicMeta(array $config): array
    {
        $out = $config;
        unset($out['access_token'], $out['app_secret'], $out['webhook_verify_token'], $out['token'], $out['password']);

        return $out;
    }

    private function ensureRuntime(string $tenantId, string $channelType): void
    {
        if (! isset($this->runtime[$tenantId][$channelType])) {
            $this->runtime[$tenantId][$channelType] = $this->state($tenantId, $channelType);
        }
    }
}

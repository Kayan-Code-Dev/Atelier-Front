<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Infrastructure\Channel;

use DressnMore\SmartAssistantProduct\Domain\ChannelConnectionStoreInterface;

/**
 * In-memory store for unit/smoke tests.
 */
final class InMemoryChannelConnectionStore implements ChannelConnectionStoreInterface
{
    /** @var array<string, array<string, array<string, mixed>>> */
    private array $connections = [];

    /** @var array<string, array<string, mixed>> */
    private array $secrets = [];

    public function connect(string $tenantId, string $channelType, array $config = []): void
    {
        $this->secrets[$tenantId][$channelType] = $config;
        $this->connections[$tenantId][$channelType] = [
            'status' => 'connected',
            'connected_at' => now()->toIso8601String(),
            'config' => $this->redact($config),
            'last_sync_at' => now()->toIso8601String(),
            'inbox' => [],
            'comments' => [],
            'outbox' => [],
            'live_api' => false,
            'auto_reply_enabled' => (bool) ($config['auto_reply_enabled'] ?? false),
            'auto_reply_mode' => (string) ($config['auto_reply_mode'] ?? 'template'),
            'external_account_id' => $config['phone_number_id'] ?? $config['page_id'] ?? null,
        ];
    }

    public function disconnect(string $tenantId, string $channelType): void
    {
        if (isset($this->connections[$tenantId][$channelType])) {
            $this->connections[$tenantId][$channelType]['status'] = 'disconnected';
            $this->connections[$tenantId][$channelType]['auto_reply_enabled'] = false;
            $this->connections[$tenantId][$channelType]['external_account_id'] = null;
        }
        unset($this->secrets[$tenantId][$channelType]);
    }

    public function updateSettings(string $tenantId, string $channelType, array $settings): void
    {
        if (! isset($this->connections[$tenantId][$channelType])) {
            return;
        }
        if (array_key_exists('auto_reply_enabled', $settings)) {
            $this->connections[$tenantId][$channelType]['auto_reply_enabled'] = (bool) $settings['auto_reply_enabled'];
            if (isset($this->secrets[$tenantId][$channelType])) {
                $this->secrets[$tenantId][$channelType]['auto_reply_enabled'] = (bool) $settings['auto_reply_enabled'];
            }
        }
        if (array_key_exists('auto_reply_mode', $settings) && is_string($settings['auto_reply_mode'])) {
            $this->connections[$tenantId][$channelType]['auto_reply_mode'] = $settings['auto_reply_mode'];
            if (isset($this->secrets[$tenantId][$channelType])) {
                $this->secrets[$tenantId][$channelType]['auto_reply_mode'] = $settings['auto_reply_mode'];
            }
        }
    }

    public function isConnected(string $tenantId, string $channelType): bool
    {
        return ($this->connections[$tenantId][$channelType]['status'] ?? null) === 'connected';
    }

    public function state(string $tenantId, string $channelType): array
    {
        return $this->connections[$tenantId][$channelType] ?? [
            'status' => 'disconnected',
            'connected_at' => null,
            'config' => [],
            'last_sync_at' => null,
            'inbox' => [],
            'comments' => [],
            'outbox' => [],
            'live_api' => false,
            'auto_reply_enabled' => false,
            'auto_reply_mode' => 'off',
            'external_account_id' => null,
        ];
    }

    public function credentials(string $tenantId, string $channelType): ?array
    {
        if (! $this->isConnected($tenantId, $channelType)) {
            return null;
        }
        $cfg = $this->secrets[$tenantId][$channelType] ?? null;
        if (! is_array($cfg) || empty($cfg['access_token'])) {
            return null;
        }

        return [
            'phone_number_id' => (string) ($cfg['phone_number_id'] ?? $cfg['page_id'] ?? $cfg['ig_user_id'] ?? ''),
            'page_id' => (string) ($cfg['page_id'] ?? $cfg['phone_number_id'] ?? $cfg['ig_user_id'] ?? ''),
            'ig_user_id' => (string) ($cfg['ig_user_id'] ?? $cfg['page_id'] ?? ''),
            'access_token' => (string) $cfg['access_token'],
            'webhook_verify_token' => $cfg['webhook_verify_token'] ?? null,
            'app_secret' => $cfg['app_secret'] ?? null,
            'auto_reply_enabled' => (bool) ($cfg['auto_reply_enabled'] ?? false),
            'auto_reply_mode' => (string) ($cfg['auto_reply_mode'] ?? 'template'),
        ];
    }

    public function findTenantIdByExternalAccount(string $channelType, string $externalAccountId): ?string
    {
        foreach ($this->connections as $tenantId => $channels) {
            $state = $channels[$channelType] ?? null;
            if (! is_array($state) || ($state['status'] ?? '') !== 'connected') {
                continue;
            }
            if ((string) ($state['external_account_id'] ?? '') === $externalAccountId) {
                return (string) $tenantId;
            }
        }

        return null;
    }

    public function pushInbox(string $tenantId, string $channelType, array $message): void
    {
        $this->ensure($tenantId, $channelType);
        $this->connections[$tenantId][$channelType]['inbox'][] = $message;
    }

    public function pushComment(string $tenantId, string $channelType, array $comment): void
    {
        $this->ensure($tenantId, $channelType);
        $this->connections[$tenantId][$channelType]['comments'][] = $comment;
    }

    public function pushOutbox(string $tenantId, string $channelType, array $message): void
    {
        $this->ensure($tenantId, $channelType);
        $this->connections[$tenantId][$channelType]['outbox'][] = $message;
    }

    public function inbox(string $tenantId, string $channelType): array
    {
        return array_values($this->state($tenantId, $channelType)['inbox'] ?? []);
    }

    public function comments(string $tenantId, string $channelType): array
    {
        return array_values($this->state($tenantId, $channelType)['comments'] ?? []);
    }

    public function rememberPublicProfile(string $tenantId, string $channelType, array $profile): void
    {
        if (! isset($this->connections[$tenantId][$channelType])) {
            return;
        }

        $cfg = &$this->connections[$tenantId][$channelType]['config'];
        if (isset($profile['display_phone_number']) && $profile['display_phone_number'] !== '') {
            $cfg['display_phone_number'] = (string) $profile['display_phone_number'];
        }
        if (isset($profile['quality_rating']) && $profile['quality_rating'] !== '') {
            $cfg['quality_rating'] = (string) $profile['quality_rating'];
        }
        $name = $profile['display_name'] ?? $profile['verified_name'] ?? null;
        if (is_string($name) && $name !== '') {
            $cfg['display_name'] = $name;
        }
    }

    private function ensure(string $tenantId, string $channelType): void
    {
        if (! isset($this->connections[$tenantId][$channelType])) {
            $this->connections[$tenantId][$channelType] = $this->state($tenantId, $channelType);
        }
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function redact(array $config): array
    {
        $out = $config;
        foreach (['access_token', 'token', 'app_secret', 'webhook_verify_token', 'password'] as $secret) {
            if (isset($out[$secret]) && is_string($out[$secret]) && $out[$secret] !== '') {
                $out[$secret] = '***';
            }
        }

        return $out;
    }
}

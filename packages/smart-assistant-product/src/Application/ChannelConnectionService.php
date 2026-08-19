<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

use DressnMore\SmartAssistantProduct\Domain\ChannelConnectionStoreInterface;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use DressnMore\SmartAssistantProduct\Infrastructure\WhatsAppWeb\WhatsAppGatewayClient;
use InvalidArgumentException;
use Throwable;

final class ChannelConnectionService
{
    public function __construct(
        private readonly ChannelConnectorManager $connectors,
        private readonly ChannelConnectionStoreInterface $store,
        private readonly WhatsAppProfileSync $profileSync,
        private readonly WhatsAppGatewayClient $gateway,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listChannels(string $tenantId): array
    {
        $rows = [];
        foreach (SocialChannelCatalog::all() as $type) {
            $def = SocialChannelCatalog::definition($type);
            if ($def === null || ! $def['enabled']) {
                continue;
            }
            $rows[] = $this->channelSnapshot($tenantId, $type);
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function connect(string $tenantId, string $channelType, array $config = []): array
    {
        $this->assertChannel($channelType);
        $this->connectors->for($channelType)->connect($tenantId, $config);

        return $this->channelSnapshot($tenantId, $channelType);
    }

    /**
     * @return array<string, mixed>
     */
    public function disconnect(string $tenantId, string $channelType): array
    {
        $this->assertChannel($channelType);
        $this->connectors->for($channelType)->disconnect($tenantId);

        return $this->channelSnapshot($tenantId, $channelType);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function updateChannelSettings(string $tenantId, string $channelType, array $settings): array
    {
        $this->assertChannel($channelType);
        $this->store->updateSettings($tenantId, $channelType, $settings);

        return $this->channelSnapshot($tenantId, $channelType);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function ingestMessage(string $tenantId, string $channelType, array $payload): array
    {
        $this->assertChannel($channelType);

        return $this->connectors->for($channelType)->receiveMessage($tenantId, $payload);
    }

    /**
     * @param array<string, mixed> $message
     */
    public function replyMessage(string $tenantId, string $channelType, array $message): void
    {
        $this->assertChannel($channelType);
        $this->connectors->for($channelType)->sendMessage($tenantId, $message);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function ingestComment(string $tenantId, string $channelType, array $payload): array
    {
        $this->assertChannel($channelType);

        return $this->connectors->for($channelType)->receiveComment($tenantId, $payload);
    }

    /**
     * @param array<string, mixed> $reply
     */
    public function replyComment(string $tenantId, string $channelType, array $reply): void
    {
        $this->assertChannel($channelType);
        $this->connectors->for($channelType)->replyComment($tenantId, $reply);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listMessages(string $tenantId, ?string $channelType = null): array
    {
        $types = $channelType !== null ? [$channelType] : SocialChannelCatalog::all();
        $all = [];
        foreach ($types as $type) {
            if (! SocialChannelCatalog::isValid($type)) {
                continue;
            }
            foreach ($this->store->inbox($tenantId, $type) as $msg) {
                $all[] = $msg;
            }
        }

        return $all;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listComments(string $tenantId, ?string $channelType = null): array
    {
        $types = $channelType !== null ? [$channelType] : [SocialChannelCatalog::FACEBOOK, SocialChannelCatalog::INSTAGRAM];
        $all = [];
        foreach ($types as $type) {
            if (! SocialChannelCatalog::isValid($type)) {
                continue;
            }
            foreach ($this->store->comments($tenantId, $type) as $cmt) {
                $all[] = $cmt;
            }
        }

        return $all;
    }

    public function verifyWebhook(string $tenantId, string $channelType, array $headers, string $body): bool
    {
        $this->assertChannel($channelType);

        return $this->connectors->for($channelType)->verifyWebhook($tenantId, $headers, $body);
    }

    public function findTenantIdByExternalAccount(string $channelType, string $externalAccountId): ?string
    {
        return $this->store->findTenantIdByExternalAccount($channelType, $externalAccountId);
    }

    /**
     * @return array<string, mixed>
     */
    private function channelSnapshot(string $tenantId, string $channelType): array
    {
        $def = SocialChannelCatalog::definition($channelType) ?? [
            'label' => $channelType,
            'label_ar' => $channelType,
            'supports' => [],
            'enabled' => true,
        ];
        $state = $this->store->state($tenantId, $channelType);
        $waWeb = $channelType === SocialChannelCatalog::WHATSAPP
            && (bool) config('smart-assistant-product.whatsapp_web.enabled', false);

        $phoneNumber = null;
        $displayName = $state['config']['display_name'] ?? null;

        if ($waWeb) {
            $sessionKey = app(WhatsAppNumberService::class)->resolveConnectedSessionKey((int) $tenantId)
                ?: $tenantId;
            [$gwStatus, $phoneNumber, $displayName] = $this->syncWhatsAppWebSnapshot(
                $sessionKey,
                $displayName,
                $tenantId,
            );
            $connected = $gwStatus === 'connected';
            $state['status'] = $gwStatus;
        } else {
            if ($channelType === SocialChannelCatalog::WHATSAPP && ($state['status'] ?? '') === 'connected') {
                $this->profileSync->ensureStoredForTenant($tenantId);
                $state = $this->store->state($tenantId, $channelType);
            }
            $connected = ($state['status'] ?? '') === 'connected';
            if ($channelType === SocialChannelCatalog::WHATSAPP) {
                $phoneNumber = $state['config']['display_phone_number'] ?? null;
            }
        }
        $live = in_array($channelType, [
            SocialChannelCatalog::WHATSAPP,
            SocialChannelCatalog::FACEBOOK,
            SocialChannelCatalog::INSTAGRAM,
        ], true);

        return [
            'type' => $channelType,
            'label' => $def['label'],
            'label_ar' => $def['label_ar'],
            'supports' => $def['supports'],
            'status' => $state['status'] ?? 'disconnected',
            'connected_at' => $state['connected_at'] ?? null,
            'last_sync_at' => $state['last_sync_at'] ?? null,
            'external_account_id' => $state['external_account_id']
                ?? ($state['config']['phone_number_id'] ?? $state['config']['page_id'] ?? null),
            'phone_number' => $channelType === SocialChannelCatalog::WHATSAPP ? $phoneNumber : null,
            'display_name' => $displayName,
            'phone_number_id' => $channelType === SocialChannelCatalog::WHATSAPP
                ? ($state['external_account_id'] ?? $state['config']['phone_number_id'] ?? null)
                : null,
            'live_api' => $live && $connected && (bool) ($state['live_api'] ?? true),
            'mode' => $live ? 'live' : 'stub',
            'auto_reply_enabled' => (bool) ($state['auto_reply_enabled'] ?? false),
            'auto_reply_mode' => (string) ($state['auto_reply_mode'] ?? 'off'),
            'webhook_url' => url('/api/webhooks/smart-assistant/'.$channelType),
        ];
    }

    private function assertChannel(string $channelType): void
    {
        if (! SocialChannelCatalog::isValid($channelType)) {
            throw new InvalidArgumentException("Unsupported channel: {$channelType}");
        }
        $def = SocialChannelCatalog::definition($channelType);
        if ($def === null || ! $def['enabled']) {
            throw new InvalidArgumentException("Channel disabled: {$channelType}");
        }
    }

    /**
     * @return array{0:string,1:?string,2:?string} [status, phone, display_name]
     */
    private function syncWhatsAppWebSnapshot(string $sessionKey, ?string $fallbackName, string $tenantId): array
    {
        try {
            $gw = $this->gateway->status($sessionKey);
        } catch (Throwable) {
            return ['disconnected', null, $fallbackName];
        }

        $session = is_array($gw['session'] ?? null) ? $gw['session'] : [];
        $raw = (string) ($session['status'] ?? 'disconnected');
        $status = match ($raw) {
            'open', 'connected' => 'connected',
            'qr_required', 'connecting', 'reconnecting' => 'connecting',
            default => 'disconnected',
        };

        $phone = isset($session['phone']) ? (string) $session['phone'] : null;
        $name = isset($session['display_name']) ? (string) $session['display_name'] : $fallbackName;

        if ($status === 'connected' && $phone !== null && $phone !== '') {
            $this->store->rememberPublicProfile($tenantId, SocialChannelCatalog::WHATSAPP, [
                'display_phone_number' => $phone,
                'display_name' => $name,
            ]);
        }

        return [$status, $phone !== '' ? $phone : null, $name !== '' ? $name : null];
    }
}

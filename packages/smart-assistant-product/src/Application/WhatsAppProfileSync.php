<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

use DressnMore\SmartAssistantProduct\Domain\ChannelConnectionStoreInterface;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaWhatsAppCloudClient;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Resolves Meta phone_number_id → human-readable WhatsApp business number.
 */
final class WhatsAppProfileSync
{
    public function __construct(
        private readonly ChannelConnectionStoreInterface $store,
        private readonly MetaWhatsAppCloudClient $client,
    ) {}

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function enrichConnectConfig(array $config): array
    {
        $phoneNumberId = trim((string) ($config['phone_number_id'] ?? ''));
        $accessToken = trim((string) ($config['access_token'] ?? ''));
        if ($phoneNumberId === '' || $accessToken === '') {
            return $config;
        }

        return array_merge($config, $this->fetchProfileFields($phoneNumberId, $accessToken));
    }

    public function ensureStoredForTenant(string $tenantId): void
    {
        if (($this->store->state($tenantId, SocialChannelCatalog::WHATSAPP)['status'] ?? '') !== 'connected') {
            return;
        }

        $state = $this->store->state($tenantId, SocialChannelCatalog::WHATSAPP);
        $existing = (string) ($state['config']['display_phone_number'] ?? '');
        if ($existing !== '') {
            return;
        }

        $creds = $this->store->credentials($tenantId, SocialChannelCatalog::WHATSAPP);
        if ($creds === null) {
            return;
        }

        $fields = $this->fetchProfileFields(
            (string) $creds['phone_number_id'],
            (string) $creds['access_token'],
        );
        if (($fields['display_phone_number'] ?? '') === '') {
            return;
        }

        $this->store->rememberPublicProfile($tenantId, SocialChannelCatalog::WHATSAPP, $fields);
    }

    /**
     * @return array{display_phone_number?:string, verified_name?:string, quality_rating?:string}
     */
    private function fetchProfileFields(string $phoneNumberId, string $accessToken): array
    {
        try {
            $profile = $this->client->fetchPhoneNumberProfile($phoneNumberId, $accessToken);
            $out = [];
            if (filled($profile['display_phone_number'] ?? null)) {
                $out['display_phone_number'] = (string) $profile['display_phone_number'];
            }
            if (filled($profile['verified_name'] ?? null)) {
                $out['display_name'] = (string) $profile['verified_name'];
            }
            if (filled($profile['quality_rating'] ?? null)) {
                $out['quality_rating'] = (string) $profile['quality_rating'];
            }

            return $out;
        } catch (Throwable $e) {
            Log::warning('whatsapp.profile_sync_failed', [
                'phone_number_id' => $phoneNumberId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}

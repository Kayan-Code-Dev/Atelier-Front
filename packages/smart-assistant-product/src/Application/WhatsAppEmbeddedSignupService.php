<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaEmbeddedSignupClient;
use Illuminate\Support\Facades\Log;
use Throwable;

final class WhatsAppEmbeddedSignupService
{
    public function __construct(
        private readonly ChannelConnectionService $channels,
        private readonly MetaEmbeddedSignupClient $meta,
    ) {}

    /**
     * Build Meta-hosted Embedded Signup URL for a tenant session.
     *
     * @return array{url:string,redirect_uri:string,app_id:string,config_id:string,enabled:bool}
     */
    public function onboardInfo(?string $tenantId = null): array
    {
        $enabled = (bool) config('smart-assistant-product.whatsapp.embedded.enabled', false);
        $appId = (string) config('smart-assistant-product.whatsapp.embedded.app_id', '');
        $configId = (string) config('smart-assistant-product.whatsapp.embedded.config_id', '');
        $redirectUri = (string) config(
            'smart-assistant-product.whatsapp.embedded.redirect_uri',
            url('/api/smart-assistant/whatsapp/embedded-signup/callback')
        );
        $configuredUrl = trim((string) config('smart-assistant-product.whatsapp.embedded.onboard_url', ''));

        if ($configuredUrl !== '') {
            $url = $configuredUrl;
        } else {
            $extras = json_encode([
                'version' => 'v4',
                'sessionInfoVersion' => '3',
                'featureType' => (string) config(
                    'smart-assistant-product.whatsapp.embedded.feature_type',
                    'whatsapp_business_app_onboarding'
                ),
            ], JSON_UNESCAPED_SLASHES);
            $url = 'https://business.facebook.com/messaging/whatsapp/onboard/?'
                .http_build_query([
                    'app_id' => $appId,
                    'config_id' => $configId,
                    'extras' => $extras,
                    'redirect_uri' => $redirectUri,
                ]);
        }

        if ($tenantId !== null && $tenantId !== '') {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator.'state='.urlencode(base64_encode(json_encode([
                'tenant_id' => $tenantId,
                'ts' => time(),
            ], JSON_UNESCAPED_UNICODE)));
        }

        return [
            'enabled' => $enabled && $appId !== '' && ($configId !== '' || $configuredUrl !== ''),
            'url' => $url,
            'redirect_uri' => $redirectUri,
            'app_id' => $appId,
            'config_id' => $configId,
            'frontend_return_url' => (string) config(
                'smart-assistant-product.whatsapp.embedded.frontend_return_url',
                'https://dressnmore.it.com/smart-assistant'
            ),
        ];
    }

    /**
     * Complete connect after Embedded Signup returns code + asset IDs.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function complete(string $tenantId, array $payload): array
    {
        $phoneNumberId = trim((string) ($payload['phone_number_id'] ?? ''));
        $wabaId = trim((string) ($payload['waba_id'] ?? ''));
        $code = trim((string) ($payload['code'] ?? ''));
        $accessToken = trim((string) ($payload['access_token'] ?? ''));

        if ($phoneNumberId === '') {
            throw new \InvalidArgumentException('phone_number_id مطلوب');
        }

        if ($accessToken === '' && $code !== '') {
            $exchanged = $this->meta->exchangeCode($code);
            $accessToken = $exchanged['access_token'];
        }

        if ($accessToken === '') {
            throw new \InvalidArgumentException('code أو access_token مطلوب لإكمال الربط');
        }

        if ($wabaId !== '') {
            try {
                $this->meta->subscribeAppToWaba($wabaId, $accessToken);
            } catch (Throwable $e) {
                Log::warning('whatsapp.embedded.subscribe_failed', ['error' => $e->getMessage()]);
            }
        }

        $featureType = (string) config(
            'smart-assistant-product.whatsapp.embedded.feature_type',
            'whatsapp_business_app_onboarding'
        );
        // Coexistence numbers are already registered; skip hard-fail on register.
        if ($featureType !== 'whatsapp_business_app_onboarding') {
            try {
                $this->meta->registerPhoneNumber($phoneNumberId, $accessToken);
            } catch (Throwable $e) {
                Log::warning('whatsapp.embedded.register_failed', ['error' => $e->getMessage()]);
            }
        }

        return $this->channels->connect($tenantId, SocialChannelCatalog::WHATSAPP, [
            'phone_number_id' => $phoneNumberId,
            'access_token' => $accessToken,
            'waba_id' => $wabaId !== '' ? $wabaId : null,
            'display_name' => $payload['display_name'] ?? null,
            'auto_reply_enabled' => array_key_exists('auto_reply_enabled', $payload)
                ? (bool) $payload['auto_reply_enabled']
                : true,
            'auto_reply_mode' => (string) ($payload['auto_reply_mode'] ?? 'template'),
        ]);
    }
}

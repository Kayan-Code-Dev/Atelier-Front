<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Infrastructure\Meta;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Exchanges Embedded Signup codes and completes Tech Provider onboarding steps.
 */
final class MetaEmbeddedSignupClient
{
    /**
     * @return array{access_token:string,token_type?:string,expires_in?:int}
     */
    public function exchangeCode(string $code): array
    {
        $appId = (string) config('smart-assistant-product.whatsapp.embedded.app_id', '');
        $appSecret = (string) config('smart-assistant-product.whatsapp.app_secret', '');
        if ($appId === '' || $appSecret === '') {
            throw new RuntimeException('META_WHATSAPP_APP_ID و META_WHATSAPP_APP_SECRET مطلوبان لإكمال Embedded Signup');
        }

        $base = rtrim((string) config('smart-assistant-product.whatsapp.api_base'), '/');
        $version = (string) config('smart-assistant-product.whatsapp.graph_version', 'v21.0');
        $url = "{$base}/{$version}/oauth/access_token";

        $response = Http::acceptJson()
            ->timeout((int) config('smart-assistant-product.whatsapp.timeout', 20))
            ->get($url, [
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'code' => $code,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Token exchange failed: HTTP '.$response->status().' '.$response->body());
        }

        $json = $response->json() ?? [];
        $token = (string) ($json['access_token'] ?? '');
        if ($token === '') {
            throw new RuntimeException('Token exchange returned empty access_token');
        }

        return [
            'access_token' => $token,
            'token_type' => isset($json['token_type']) ? (string) $json['token_type'] : null,
            'expires_in' => isset($json['expires_in']) ? (int) $json['expires_in'] : null,
        ];
    }

    public function subscribeAppToWaba(string $wabaId, string $accessToken): void
    {
        if ($wabaId === '') {
            return;
        }

        $base = rtrim((string) config('smart-assistant-product.whatsapp.api_base'), '/');
        $version = (string) config('smart-assistant-product.whatsapp.graph_version', 'v21.0');
        $url = "{$base}/{$version}/{$wabaId}/subscribed_apps";

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout((int) config('smart-assistant-product.whatsapp.timeout', 20))
            ->post($url);

        if (! $response->successful()) {
            throw new RuntimeException('WABA subscribe failed: HTTP '.$response->status().' '.$response->body());
        }
    }

    public function registerPhoneNumber(string $phoneNumberId, string $accessToken, string $pin = '000000'): void
    {
        if ($phoneNumberId === '') {
            return;
        }

        $base = rtrim((string) config('smart-assistant-product.whatsapp.api_base'), '/');
        $version = (string) config('smart-assistant-product.whatsapp.graph_version', 'v21.0');
        $url = "{$base}/{$version}/{$phoneNumberId}/register";

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout((int) config('smart-assistant-product.whatsapp.timeout', 20))
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'pin' => $pin,
            ]);

        // Already registered is acceptable for coexistence / re-connect.
        if (! $response->successful()) {
            $body = $response->body();
            if (str_contains($body, 'already') || str_contains($body, '133016')) {
                return;
            }
            throw new RuntimeException('Phone register failed: HTTP '.$response->status().' '.$body);
        }
    }
}

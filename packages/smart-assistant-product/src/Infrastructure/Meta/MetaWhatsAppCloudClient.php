<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Infrastructure\Meta;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final class MetaWhatsAppCloudClient
{
    public function __construct(
        private readonly ?string $apiBase = null,
        private readonly ?string $graphVersion = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function sendText(string $phoneNumberId, string $accessToken, string $to, string $text): array
    {
        $to = preg_replace('/\D+/', '', $to) ?? $to;
        $url = sprintf(
            '%s/%s/%s/messages',
            rtrim($this->apiBase ?? (string) config('smart-assistant-product.whatsapp.api_base'), '/'),
            $this->graphVersion ?? (string) config('smart-assistant-product.whatsapp.graph_version'),
            $phoneNumberId
        );

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout((int) config('smart-assistant-product.whatsapp.timeout', 20))
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => mb_substr($text, 0, 4096),
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'WhatsApp send failed: HTTP '.$response->status().' '.$response->body()
            );
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        return $json;
    }

    /**
     * @return array{display_phone_number:?string, verified_name:?string, quality_rating:?string}
     */
    public function fetchPhoneNumberProfile(string $phoneNumberId, string $accessToken): array
    {
        $url = sprintf(
            '%s/%s/%s',
            rtrim($this->apiBase ?? (string) config('smart-assistant-product.whatsapp.api_base'), '/'),
            $this->graphVersion ?? (string) config('smart-assistant-product.whatsapp.graph_version'),
            $phoneNumberId,
        );

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout((int) config('smart-assistant-product.whatsapp.timeout', 20))
            ->get($url, [
                'fields' => 'display_phone_number,verified_name,quality_rating',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'WhatsApp profile fetch failed: HTTP '.$response->status().' '.$response->body()
            );
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        return [
            'display_phone_number' => isset($json['display_phone_number'])
                ? (string) $json['display_phone_number']
                : null,
            'verified_name' => isset($json['verified_name']) ? (string) $json['verified_name'] : null,
            'quality_rating' => isset($json['quality_rating']) ? (string) $json['quality_rating'] : null,
        ];
    }
}

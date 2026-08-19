<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Infrastructure\Meta;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Meta Graph client for Facebook Page Messenger + Instagram Messaging + comment replies.
 */
final class MetaMessengerGraphClient
{
    public function __construct(
        private readonly ?string $apiBase = null,
        private readonly ?string $graphVersion = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function sendText(string $pageOrAccountId, string $accessToken, string $recipientId, string $text): array
    {
        $url = sprintf(
            '%s/%s/%s/messages',
            rtrim($this->apiBase ?? (string) config('smart-assistant-product.messenger.api_base', config('smart-assistant-product.whatsapp.api_base')), '/'),
            $this->graphVersion ?? (string) config('smart-assistant-product.messenger.graph_version', config('smart-assistant-product.whatsapp.graph_version', 'v21.0')),
            $pageOrAccountId !== '' ? $pageOrAccountId : 'me'
        );

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout((int) config('smart-assistant-product.messenger.timeout', 20))
            ->post($url, [
                'recipient' => ['id' => $recipientId],
                'messaging_type' => 'RESPONSE',
                'message' => [
                    'text' => mb_substr($text, 0, 2000),
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Messenger send failed: HTTP '.$response->status().' '.$response->body()
            );
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    public function replyToComment(string $commentId, string $accessToken, string $text): array
    {
        $url = sprintf(
            '%s/%s/%s/comments',
            rtrim($this->apiBase ?? (string) config('smart-assistant-product.messenger.api_base', config('smart-assistant-product.whatsapp.api_base')), '/'),
            $this->graphVersion ?? (string) config('smart-assistant-product.messenger.graph_version', config('smart-assistant-product.whatsapp.graph_version', 'v21.0')),
            $commentId
        );

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout((int) config('smart-assistant-product.messenger.timeout', 20))
            ->post($url, [
                'message' => mb_substr($text, 0, 8000),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Comment reply failed: HTTP '.$response->status().' '.$response->body()
            );
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        return $json;
    }
}

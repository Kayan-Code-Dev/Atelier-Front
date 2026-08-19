<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Infrastructure\Meta;

final class MetaWebhookSignatureVerifier
{
    public function isValid(string $rawBody, ?string $signatureHeader, ?string $appSecret): bool
    {
        $secret = $appSecret ?: (string) config('smart-assistant-product.whatsapp.app_secret', '');
        if ($secret === '') {
            // Allow when secret not configured (dev) — production should set META_WHATSAPP_APP_SECRET.
            return ! (bool) config('smart-assistant-product.whatsapp.require_signature', false);
        }

        if ($signatureHeader === null || $signatureHeader === '') {
            return false;
        }

        $provided = str_starts_with($signatureHeader, 'sha256=')
            ? substr($signatureHeader, 7)
            : $signatureHeader;

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $provided);
    }
}

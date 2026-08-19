<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Infrastructure\WhatsAppWeb;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * HTTP client for the DressnMore WhatsApp Gateway (Baileys-based).
 * All traffic is same-host (127.0.0.1) protected by a shared secret.
 */
final class WhatsAppGatewayClient
{
    private function base(): string
    {
        return rtrim((string) config('smart-assistant-product.whatsapp_web.gateway_url', 'http://127.0.0.1:3101'), '/');
    }

    private function secret(): string
    {
        $secret = (string) config('smart-assistant-product.whatsapp_web.gateway_secret', '');
        if ($secret === '') {
            throw new RuntimeException('WhatsApp gateway secret is not configured');
        }

        return $secret;
    }

    /** @return array<string, mixed> */
    public function createSession(string $sessionKey): array
    {
        return $this->post('/session/'.$this->encode($sessionKey).'/create');
    }

    /** @return array<string, mixed> */
    public function status(string $sessionKey): array
    {
        return $this->get('/session/'.$this->encode($sessionKey).'/status');
    }

    /** @return array{qr:?string,status:string} */
    public function qr(string $sessionKey): array
    {
        return $this->get('/session/'.$this->encode($sessionKey).'/qr');
    }

    /** @return array<string, mixed> */
    public function send(string $sessionKey, string $to, string $text): array
    {
        return $this->post('/session/'.$this->encode($sessionKey).'/send', ['to' => $to, 'text' => $text]);
    }

    /** @return array<string, mixed> */
    public function sendDocument(string $sessionKey, string $to, string $filename, string $bytes, ?string $caption = null): array
    {
        return $this->post('/session/'.$this->encode($sessionKey).'/send-document', [
            'to' => $to,
            'filename' => $filename,
            'mimetype' => 'application/pdf',
            'data' => base64_encode($bytes),
            'caption' => $caption,
        ], 90);
    }

    /** @return array<string, mixed> */
    public function disconnect(string $sessionKey): array
    {
        return $this->post('/session/'.$this->encode($sessionKey).'/disconnect');
    }

    private function encode(string $sessionKey): string
    {
        return rawurlencode($sessionKey);
    }

    /** @return array<string, mixed> */
    private function get(string $path): array
    {
        $res = Http::withHeaders(['X-Gateway-Secret' => $this->secret()])
            ->timeout(15)
            ->get($this->base().$path);
        if (! $res->ok()) {
            throw new RuntimeException('gateway: '.$res->body());
        }

        return $res->json() ?? [];
    }

    /** @return array<string, mixed> */
    private function post(string $path, array $body = [], int $timeoutSeconds = 30): array
    {
        $res = Http::withHeaders(['X-Gateway-Secret' => $this->secret()])
            ->timeout($timeoutSeconds)
            ->connectTimeout(10)
            ->post($this->base().$path, $body);
        if (! $res->ok()) {
            throw new RuntimeException('gateway: '.$res->body());
        }

        return $res->json() ?? [];
    }
}

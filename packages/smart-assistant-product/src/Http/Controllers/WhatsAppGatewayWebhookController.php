<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Http\Controllers;

use DressnMore\SmartAssistantProduct\Application\ChannelConnectionService;
use DressnMore\SmartAssistantProduct\Application\WhatsAppNumberService;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use DressnMore\SmartAssistantProduct\Domain\WhatsAppSessionKey;
use DressnMore\SmartAssistantProduct\Jobs\ProcessWhatsAppInboundMessage;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantChannelConnection;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantInboundMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Internal callback from the WhatsApp Gateway (QR pairing path).
 * NOT a Meta webhook — authenticated by shared secret.
 * Path: /api/webhooks/whatsapp-gateway
 */
final class WhatsAppGatewayWebhookController
{
    public function __construct(
        private readonly ChannelConnectionService $channels,
        private readonly WhatsAppNumberService $numbers,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $expected = (string) config('smart-assistant-product.whatsapp_web.gateway_secret', '');
        $provided = (string) $request->header('X-Gateway-Secret', '');
        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $data = $request->validate([
            'event' => ['required', 'string', 'in:whatsapp.message.received,whatsapp.state,whatsapp.qr_updated,whatsapp.delivery'],
            'tenant_id' => ['required', 'max:32'],
            'message' => ['nullable', 'array'],
            'message_id' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'max:32'],
            'connection' => ['nullable', 'array'],
        ]);

        $parsed = WhatsAppSessionKey::parse((string) $data['tenant_id']);
        $tenantId = (string) $parsed['tenant_id'];
        // never trust a tenant id that does not exist
        $exists = DB::connection('central')->table('tenants')->where('id', (int) $tenantId)->exists();
        if (! $exists) {
            Log::warning('wa_gateway.unknown_tenant', ['tenant_id' => $data['tenant_id']]);

            return response()->json(['ok' => false], 202);
        }

        $connection = $this->numbers->findBySessionKey((string) $data['tenant_id']);
        $sessionKey = $connection?->session_key ?: (string) $data['tenant_id'];

        try {
            match ($data['event']) {
                'whatsapp.message.received' => $this->onMessage($tenantId, $data['message'] ?? [], $connection, $sessionKey),
                'whatsapp.delivery' => Log::info('[WA][DELIVERY]', [
                    'tenant_id' => $tenantId,
                    'message_id' => (string) ($data['message_id'] ?? ''),
                    'status' => (string) ($data['status'] ?? 'unknown'),
                ]),
                'whatsapp.state' => $this->onState($tenantId, $data['connection'] ?? [], $connection),
                default => null, // qr_updated: no-op, FE polls /qr directly
            };
        } catch (Throwable $e) {
            Log::error('wa_gateway.callback_failed', ['error' => $e->getMessage(), 'event' => $data['event']]);
        }

        return response()->json(['ok' => true], 202);
    }

    /** @param array<string, mixed> $message */
    private function onMessage(string $tenantId, array $message, ?SmartAssistantChannelConnection $connection, string $sessionKey): void
    {
        $externalId = (string) ($message['message_id'] ?? '');
        if ($externalId !== '') {
            $exists = SmartAssistantInboundMessage::query()
                ->where('channel_type', SocialChannelCatalog::WHATSAPP)
                ->where('external_message_id', $externalId)
                ->exists();
            if ($exists) {
                Log::info('[WA][INBOUND] duplicate webhook dropped', ['tenant_id' => $tenantId, 'message_id' => $externalId]);

                return;
            }
        }

        $message['session_key'] = $sessionKey;
        $message['connection_id'] = $connection?->id;
        $message['assistant_name'] = $connection?->assistant_name;

        Log::info('[WA][INBOUND]', [
            'tenant_id' => $tenantId,
            'connection_id' => $connection?->id,
            'message_id' => $externalId,
            'remote_jid' => (string) ($message['from'] ?? ''),
        ]);

        $normalized = $this->channels->ingestMessage($tenantId, SocialChannelCatalog::WHATSAPP, $message);
        $normalized['session_key'] = $sessionKey;
        $normalized['connection_id'] = $connection?->id;
        ProcessWhatsAppInboundMessage::dispatch($tenantId, $normalized);
    }

    /** @param array<string, mixed> $conn */
    private function onState(string $tenantId, array $conn, ?SmartAssistantChannelConnection $connection): void
    {
        $status = (string) ($conn['status'] ?? '');
        $mapped = match ($status) {
            'connected' => 'connected',
            'logged_out', 'disconnected', 'idle' => 'disconnected',
            default => null, // connecting / qr_required: keep row as-is
        };
        if ($mapped === null) {
            return;
        }

        $update = ['status' => $mapped, 'updated_at' => now()];
        if ($mapped === 'connected') {
            $update['connected_at'] = now();
            $update['last_sync_at'] = now();
            if (! empty($conn['phone'])) {
                $update['display_name'] = ($conn['display_name'] ?? '') !== ''
                    ? $conn['display_name']
                    : $conn['phone'];
            }
        }

        $query = DB::connection('central')->table('smart_assistant_channel_connections')
            ->where('tenant_id', (int) $tenantId)
            ->where('channel_type', SocialChannelCatalog::WHATSAPP);
        if ($connection !== null) {
            $query->where('id', $connection->id);
        }
        $query->update($update);
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Http\Controllers;

use DressnMore\SmartAssistantProduct\Application\ChannelConnectionService;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaInstagramWebhookPayloadParser;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaPageWebhookPayloadParser;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\MetaWebhookSignatureVerifier;
use DressnMore\SmartAssistantProduct\Infrastructure\Meta\WhatsAppWebhookPayloadParser;
use DressnMore\SmartAssistantProduct\Jobs\ProcessSocialInboundEvent;
use DressnMore\SmartAssistantProduct\Jobs\ProcessWhatsAppInboundMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

final class WebhookController
{
    public function __construct(
        private readonly ChannelConnectionService $channels,
        private readonly MetaWebhookSignatureVerifier $signatureVerifier,
        private readonly WhatsAppWebhookPayloadParser $whatsAppParser,
        private readonly MetaPageWebhookPayloadParser $pageParser,
        private readonly MetaInstagramWebhookPayloadParser $instagramParser,
    ) {}

    public function verify(Request $request, string $channel): Response|JsonResponse
    {
        if (! SocialChannelCatalog::isValid($channel)) {
            return response('unsupported channel', 404);
        }

        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));

        $expected = (string) config('smart-assistant-product.webhook_verify_token', 'dressnmore-sa');

        if ($mode === 'subscribe' && $token !== '' && hash_equals($expected, $token) && $challenge !== '') {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('forbidden', 403);
    }

    public function receive(Request $request, string $channel): JsonResponse
    {
        if (! SocialChannelCatalog::isValid($channel)) {
            return response()->json(['error' => 'unsupported'], 404);
        }

        $raw = $request->getContent();
        $signature = $request->header('X-Hub-Signature-256');

        if (! $this->signatureVerifier->isValid($raw, $signature, null)) {
            return response()->json(['error' => 'invalid signature'], 403);
        }

        return match ($channel) {
            SocialChannelCatalog::WHATSAPP => $this->receiveWhatsApp($request->all()),
            SocialChannelCatalog::FACEBOOK => $this->receiveFacebook($request->all()),
            SocialChannelCatalog::INSTAGRAM => $this->receiveInstagram($request->all()),
            default => response()->json(['error' => 'unsupported'], 404),
        };
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function receiveWhatsApp(array $payload): JsonResponse
    {
        try {
            $items = $this->whatsAppParser->extractInboundMessages($payload);
            foreach ($items as $item) {
                $phoneNumberId = (string) $item['phone_number_id'];
                $message = $item['message'];
                $tenantId = $this->channels->findTenantIdByExternalAccount(
                    SocialChannelCatalog::WHATSAPP,
                    $phoneNumberId
                );
                if ($tenantId === null) {
                    Log::warning('whatsapp.unbound_phone_number_id', ['phone_number_id' => $phoneNumberId]);
                    continue;
                }

                $normalized = $this->channels->ingestMessage($tenantId, SocialChannelCatalog::WHATSAPP, $message);
                ProcessWhatsAppInboundMessage::dispatch($tenantId, $normalized);
            }
        } catch (Throwable $e) {
            Log::error('whatsapp.webhook_failed', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 200);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function receiveFacebook(array $payload): JsonResponse
    {
        try {
            foreach ($this->pageParser->extract($payload) as $item) {
                $pageId = (string) $item['page_id'];
                $tenantId = $this->channels->findTenantIdByExternalAccount(
                    SocialChannelCatalog::FACEBOOK,
                    $pageId
                );
                if ($tenantId === null) {
                    Log::warning('facebook.unbound_page_id', ['page_id' => $pageId]);
                    continue;
                }

                if ($item['kind'] === 'comment') {
                    $normalized = $this->channels->ingestComment(
                        $tenantId,
                        SocialChannelCatalog::FACEBOOK,
                        $item['payload']
                    );
                    ProcessSocialInboundEvent::dispatch(
                        $tenantId,
                        SocialChannelCatalog::FACEBOOK,
                        'comment',
                        $normalized
                    );
                } else {
                    $normalized = $this->channels->ingestMessage(
                        $tenantId,
                        SocialChannelCatalog::FACEBOOK,
                        $item['payload']
                    );
                    ProcessSocialInboundEvent::dispatch(
                        $tenantId,
                        SocialChannelCatalog::FACEBOOK,
                        'message',
                        $normalized
                    );
                }
            }
        } catch (Throwable $e) {
            Log::error('facebook.webhook_failed', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 200);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function receiveInstagram(array $payload): JsonResponse
    {
        try {
            foreach ($this->instagramParser->extract($payload) as $item) {
                $igId = (string) $item['ig_id'];
                $tenantId = $this->channels->findTenantIdByExternalAccount(
                    SocialChannelCatalog::INSTAGRAM,
                    $igId
                );
                if ($tenantId === null) {
                    Log::warning('instagram.unbound_ig_id', ['ig_id' => $igId]);
                    continue;
                }

                if ($item['kind'] === 'comment') {
                    $normalized = $this->channels->ingestComment(
                        $tenantId,
                        SocialChannelCatalog::INSTAGRAM,
                        $item['payload']
                    );
                    ProcessSocialInboundEvent::dispatch(
                        $tenantId,
                        SocialChannelCatalog::INSTAGRAM,
                        'comment',
                        $normalized
                    );
                } else {
                    $normalized = $this->channels->ingestMessage(
                        $tenantId,
                        SocialChannelCatalog::INSTAGRAM,
                        $item['payload']
                    );
                    ProcessSocialInboundEvent::dispatch(
                        $tenantId,
                        SocialChannelCatalog::INSTAGRAM,
                        'message',
                        $normalized
                    );
                }
            }
        } catch (Throwable $e) {
            Log::error('instagram.webhook_failed', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 200);
        }

        return response()->json(['ok' => true]);
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Http\Controllers;

use App\Services\Auth\TenantAuthService;
use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use DressnMore\SmartAssistantProduct\Application\ChannelConnectionService;
use DressnMore\SmartAssistantProduct\Application\SmartAssistantAccessGate;
use DressnMore\SmartAssistantProduct\Application\WhatsAppEmbeddedSignupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SmartAssistantDashboardController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuthService $tenantAuthService,
        private readonly SmartAssistantAccessGate $accessGate,
        private readonly ChannelConnectionService $channels,
        private readonly WhatsAppEmbeddedSignupService $embeddedSignup,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $permissions = $this->tenantAuthService->permissionsForUser($request->user());
        $nav = $this->accessGate->navigationPayload($tenant, $permissions);
        $tenantId = (string) $tenant->id;
        $channelRows = $this->channels->listChannels($tenantId);
        $connected = count(array_filter($channelRows, static fn (array $c): bool => ($c['status'] ?? '') === 'connected'));
        $embedded = $this->embeddedSignup->onboardInfo($tenantId);

        return ApiResponse::success([
            'title' => $nav['label_ar'] ?? 'المساعد الذكي',
            'title_en' => $nav['label'] ?? 'Smart Assistant',
            'status' => 'ready',
            'mode' => 'live',
            'auto_reply' => true,
            'execution' => [
                'live_meta_api' => true,
                'whatsapp_live' => true,
                'auto_reply' => true,
                'llm' => false,
            ],
            'summary' => [
                'channels_available' => count($channelRows),
                'channels_connected' => $connected,
                'messages' => count($this->channels->listMessages($tenantId)),
                'comments' => count($this->channels->listComments($tenantId)),
            ],
            'sections' => [
                'channels' => true,
                'messages' => true,
                'comments' => true,
                'automations' => true,
                'settings' => true,
            ],
            'navigation' => $nav,
            'webhook_url' => url('/api/webhooks/smart-assistant/whatsapp'),
            'embedded_signup' => $embedded,
            'message' => $embedded['enabled']
                ? 'واتساب / فيسبوك / إنستغرام جاهزين — الربط الحي والرد التلقائي مفعّل'
                : 'القنوات الاجتماعية جاهزة للربط الحي',
        ]);
    }

    public function navigation(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $permissions = $this->tenantAuthService->permissionsForUser($request->user());

        return ApiResponse::success(
            $this->accessGate->navigationPayload($tenant, $permissions)
        );
    }

    public function settings(): JsonResponse
    {
        $tenantId = (string) $this->tenantContext->requireTenant()->id;
        $channels = $this->channels->listChannels($tenantId);
        $wa = collect($channels)->firstWhere('type', 'whatsapp');

        return ApiResponse::success([
            'auto_reply_enabled' => (bool) ($wa['auto_reply_enabled'] ?? false),
            'auto_reply_mode' => (string) ($wa['auto_reply_mode'] ?? 'template'),
            'reply_to_messages' => true,
            'reply_to_comments' => true,
            'webhook_url' => url('/api/webhooks/smart-assistant/whatsapp'),
            'webhook_verify_token_hint' => 'استخدم SMART_ASSISTANT_WEBHOOK_VERIFY_TOKEN من إعدادات السيرفر',
            'channels' => config('smart-assistant-product.channels'),
            'whatsapp' => [
                'live_api' => true,
                'graph_version' => config('smart-assistant-product.whatsapp.graph_version'),
                'required_fields' => ['phone_number_id', 'access_token'],
                'status' => $wa['status'] ?? 'disconnected',
            ],
            'note' => 'بعد الربط: في Meta Developer → WhatsApp → Configuration ضع Callback URL على webhook_url ونفس Verify Token',
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['nullable', 'string', 'in:whatsapp,facebook,instagram'],
            'auto_reply_enabled' => ['nullable', 'boolean'],
            'auto_reply_mode' => ['nullable', 'string', 'in:template,planner,off'],
        ]);

        $channel = (string) ($data['channel'] ?? 'whatsapp');
        $tenantId = (string) $this->tenantContext->requireTenant()->id;

        $payload = [];
        if (array_key_exists('auto_reply_enabled', $data)) {
            $payload['auto_reply_enabled'] = (bool) $data['auto_reply_enabled'];
        }
        if (array_key_exists('auto_reply_mode', $data)) {
            $payload['auto_reply_mode'] = (string) $data['auto_reply_mode'];
        }

        $snapshot = $this->channels->updateChannelSettings($tenantId, $channel, $payload);

        return ApiResponse::success($snapshot, 'تم تحديث إعدادات القناة');
    }
}

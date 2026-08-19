<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Http\Controllers;

use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use DressnMore\SmartAssistantProduct\Application\ChannelConnectionService;
use DressnMore\SmartAssistantProduct\Domain\SocialChannelCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class ChannelController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ChannelConnectionService $channels,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (string) $this->tenantContext->requireTenant()->id;

        return ApiResponse::success([
            'channels' => $this->channels->listChannels($tenantId),
            'supported' => SocialChannelCatalog::all(),
        ]);
    }

    public function connect(Request $request, string $channel): JsonResponse
    {
        $data = $request->validate([
            'page_id' => ['nullable', 'string', 'max:120'],
            'ig_user_id' => ['nullable', 'string', 'max:120'],
            'phone_number_id' => ['nullable', 'string', 'max:120'],
            'access_token' => ['nullable', 'string', 'max:4000'],
            'webhook_verify_token' => ['nullable', 'string', 'max:255'],
            'waba_id' => ['nullable', 'string', 'max:120'],
            'display_name' => ['nullable', 'string', 'max:190'],
            'auto_reply_enabled' => ['nullable', 'boolean'],
            'auto_reply_mode' => ['nullable', 'string', 'in:template,planner,off'],
        ]);

        if ($channel === 'whatsapp') {
            if (empty($data['phone_number_id']) || empty($data['access_token'])) {
                return ApiResponse::error('phone_number_id و access_token مطلوبان لربط واتساب', 422);
            }
            $data['auto_reply_enabled'] = array_key_exists('auto_reply_enabled', $data)
                ? (bool) $data['auto_reply_enabled']
                : true;
            $data['auto_reply_mode'] = $data['auto_reply_mode'] ?? 'template';
        }

        if ($channel === 'facebook') {
            if (empty($data['page_id']) || empty($data['access_token'])) {
                return ApiResponse::error('page_id و access_token مطلوبان لربط فيسبوك', 422);
            }
            $data['auto_reply_enabled'] = array_key_exists('auto_reply_enabled', $data)
                ? (bool) $data['auto_reply_enabled']
                : true;
            $data['auto_reply_mode'] = $data['auto_reply_mode'] ?? 'template';
        }

        if ($channel === 'instagram') {
            if ((empty($data['ig_user_id']) && empty($data['page_id'])) || empty($data['access_token'])) {
                return ApiResponse::error('ig_user_id (أو page_id) و access_token مطلوبان لربط إنستغرام', 422);
            }
            $data['auto_reply_enabled'] = array_key_exists('auto_reply_enabled', $data)
                ? (bool) $data['auto_reply_enabled']
                : true;
            $data['auto_reply_mode'] = $data['auto_reply_mode'] ?? 'template';
        }

        try {
            $snapshot = $this->channels->connect(
                (string) $this->tenantContext->requireTenant()->id,
                $channel,
                $data
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        $msg = match ($channel) {
            'whatsapp' => 'تم ربط واتساب بنجاح — تأكد من إعداد Webhook في Meta',
            'facebook' => 'تم ربط فيسبوك بنجاح — اشترك في messages و feed على Webhook الصفحة',
            'instagram' => 'تم ربط إنستغرام بنجاح — اشترك في messages/comments على Webhook',
            default => 'تم ربط القناة',
        };

        return ApiResponse::success($snapshot, $msg);
    }

    public function disconnect(string $channel): JsonResponse
    {
        try {
            $snapshot = $this->channels->disconnect(
                (string) $this->tenantContext->requireTenant()->id,
                $channel
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($snapshot, 'تم فصل القناة');
    }
}

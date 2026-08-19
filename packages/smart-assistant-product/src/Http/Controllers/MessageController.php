<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Http\Controllers;

use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use DressnMore\SmartAssistantProduct\Application\ChannelConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class MessageController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ChannelConnectionService $channels,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $channel = $request->query('channel');
        $tenantId = (string) $this->tenantContext->requireTenant()->id;

        return ApiResponse::success([
            'messages' => $this->channels->listMessages(
                $tenantId,
                is_string($channel) && $channel !== '' ? $channel : null
            ),
            'auto_reply' => true,
        ]);
    }

    public function reply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'string', 'in:whatsapp,facebook,instagram'],
            'to' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string', 'max:4000'],
        ]);

        try {
            $this->channels->replyMessage(
                (string) $this->tenantContext->requireTenant()->id,
                $data['channel'],
                [
                    'to' => $data['to'],
                    'text' => $data['text'],
                ]
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success(['sent' => true], $data['channel'] === 'whatsapp'
            ? 'تم إرسال الرد عبر واتساب'
            : 'تم جدولة الرد (وضع تجريبي)');
    }

    /** Dev/test ingest — simulates inbound social message */
    public function ingest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'string', 'in:whatsapp,facebook,instagram'],
            'from' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string', 'max:4000'],
        ]);

        try {
            $msg = $this->channels->ingestMessage(
                (string) $this->tenantContext->requireTenant()->id,
                $data['channel'],
                $data
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($msg, 'تم استلام الرسالة');
    }
}

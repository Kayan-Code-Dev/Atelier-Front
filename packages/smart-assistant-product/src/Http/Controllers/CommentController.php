<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Http\Controllers;

use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use DressnMore\SmartAssistantProduct\Application\ChannelConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class CommentController
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
            'comments' => $this->channels->listComments(
                $tenantId,
                is_string($channel) && $channel !== '' ? $channel : null
            ),
            'auto_reply' => true,
        ]);
    }

    public function reply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'string', 'in:facebook,instagram'],
            'comment_id' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string', 'max:4000'],
        ]);

        try {
            $this->channels->replyComment(
                (string) $this->tenantContext->requireTenant()->id,
                $data['channel'],
                [
                    'comment_id' => $data['comment_id'],
                    'text' => $data['text'],
                ]
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success(['sent' => true], 'تم إرسال رد التعليق');
    }

    public function ingest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'string', 'in:facebook,instagram'],
            'post_id' => ['nullable', 'string', 'max:255'],
            'from' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string', 'max:4000'],
        ]);

        try {
            $cmt = $this->channels->ingestComment(
                (string) $this->tenantContext->requireTenant()->id,
                $data['channel'],
                $data
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($cmt, 'تم استلام التعليق');
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Platform\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AiChatController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'screen' => 'chat',
            'placeholder' => 'اسأل المستشار الذكي…',
            'conversations' => [],
            'ready_for_gateway' => false,
            'note' => 'Chat shell only — no LLM or tool execution in Sprint 18A',
        ]);
    }

    public function history(): JsonResponse
    {
        return ApiResponse::success([
            'screen' => 'history',
            'items' => [],
            'note' => 'History shell — live conversations out of scope for Sprint 18A',
        ]);
    }
}

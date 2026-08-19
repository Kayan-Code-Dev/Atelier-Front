<?php

declare(strict_types=1);

namespace DressnMore\Platform\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AiUsageController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'screen' => 'usage',
            'period' => 'current_month',
            'chat_messages' => 0,
            'limit_key' => 'ai_assistant.chat_monthly.max',
            'note' => 'Usage counters wire in a later sprint',
        ]);
    }
}

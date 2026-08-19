<?php

declare(strict_types=1);

namespace DressnMore\Platform\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AiMemoryController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'screen' => 'memory',
            'entries' => [],
            'note' => 'Memory UI shell — smart memory out of scope for Sprint 18A',
        ]);
    }
}

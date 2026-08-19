<?php

declare(strict_types=1);

namespace DressnMore\Platform\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AiSettingsController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'screen' => 'settings',
            'settings' => [
                'language' => 'ar',
                'tone' => 'professional',
                'notifications' => false,
            ],
            'editable' => true,
        ]);
    }
}

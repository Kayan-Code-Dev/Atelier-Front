<?php

declare(strict_types=1);

namespace DressnMore\Platform\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AiIntegrationController
{
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'screen' => 'integrations',
            'integrations' => [],
            'note' => 'Binding registry UI only — live integrations out of scope',
        ]);
    }
}

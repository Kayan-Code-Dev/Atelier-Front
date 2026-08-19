<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdateLandingSettingsRequest;
use App\Services\Platform\LandingSettingsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class LandingSettingsController extends Controller
{
    public function __construct(
        private readonly LandingSettingsService $landing,
    ) {}

    public function show(): JsonResponse
    {
        return ApiResponse::success($this->landing->publicPayload());
    }

    public function update(UpdateLandingSettingsRequest $request): JsonResponse
    {
        $payload = $this->landing->update($request->validated());

        return ApiResponse::success($payload, 'تم حفظ إعدادات الصفحة الرئيسية');
    }
}

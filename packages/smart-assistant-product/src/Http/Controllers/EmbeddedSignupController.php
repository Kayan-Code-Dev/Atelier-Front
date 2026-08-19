<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Http\Controllers;

use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use DressnMore\SmartAssistantProduct\Application\WhatsAppEmbeddedSignupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class EmbeddedSignupController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly WhatsAppEmbeddedSignupService $embeddedSignup,
    ) {}

    public function onboardUrl(): JsonResponse
    {
        $tenantId = (string) $this->tenantContext->requireTenant()->id;

        return ApiResponse::success($this->embeddedSignup->onboardInfo($tenantId));
    }

    public function complete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone_number_id' => ['required', 'string', 'max:120'],
            'waba_id' => ['nullable', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:4000'],
            'access_token' => ['nullable', 'string', 'max:4000'],
            'display_name' => ['nullable', 'string', 'max:190'],
            'auto_reply_enabled' => ['nullable', 'boolean'],
            'auto_reply_mode' => ['nullable', 'string', 'in:template,planner,off'],
        ]);

        if (empty($data['code']) && empty($data['access_token'])) {
            return ApiResponse::error('code أو access_token مطلوب', 422);
        }

        try {
            $snapshot = $this->embeddedSignup->complete(
                (string) $this->tenantContext->requireTenant()->id,
                $data
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($snapshot, 'تم ربط واتساب بنجاح عبر Embedded Signup');
    }
}

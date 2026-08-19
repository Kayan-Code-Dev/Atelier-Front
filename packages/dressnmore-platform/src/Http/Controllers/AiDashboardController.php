<?php

declare(strict_types=1);

namespace DressnMore\Platform\Http\Controllers;

use App\Services\Auth\TenantAuthService;
use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use DressnMore\Platform\Application\AiAccessGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Initial AI Assistant dashboard shell — no Planner / Gateway / LLM.
 */
final class AiDashboardController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuthService $tenantAuthService,
        private readonly AiAccessGate $aiAccessGate,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->tenant();
        $permissions = $this->tenantAuthService->permissionsForUser($request->user());
        $nav = $this->aiAccessGate->navigationPayload($tenant, $permissions);

        return ApiResponse::success([
            'title' => $nav['label_ar'] ?? 'المستشار الذكي',
            'title_en' => $nav['label'] ?? 'AI Assistant',
            'status' => 'ready',
            'execution' => [
                'planner' => false,
                'gateway' => false,
                'llm' => false,
            ],
            'sections' => [
                'chat' => true,
                'history' => true,
                'settings' => true,
                'memory' => true,
                'integrations' => true,
                'usage' => true,
            ],
            'navigation' => $nav,
            'message' => 'واجهة أولية للمستشار الذكي — التخطيط والتنفيذ في سبرنت لاحق',
        ]);
    }

    public function navigation(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->tenant();
        $permissions = $this->tenantAuthService->permissionsForUser($request->user());

        return ApiResponse::success(
            $this->aiAccessGate->navigationPayload($tenant, $permissions)
        );
    }
}

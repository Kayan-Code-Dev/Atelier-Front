<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Http\Controllers;

use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use DressnMore\SmartAssistantProduct\Application\AiQuotaService;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantQuotaUsage;
use DressnMore\SmartAssistantProduct\Models\SmartAssistantWhatsAppConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AssistantUsageController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AiQuotaService $quotaService,
    ) {}

    public function show(): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $quota = $this->quotaService->snapshot($tenant);

        $conversations = SmartAssistantWhatsAppConversation::query()
            ->where('tenant_id', $tenant->id);

        return ApiResponse::success([
            'quota' => $quota,
            'stats' => [
                'today' => ['messages' => $this->usedToday((int) $tenant->id)],
                'week' => ['messages' => $quota['used']],
                'month' => ['messages' => $quota['used']],
            ],
            'conversations' => [
                'total' => (clone $conversations)->count(),
                'handed_off' => (clone $conversations)->where('handler', 'human')->count(),
            ],
            'top_up' => [
                'available' => ($quota['status'] ?? '') === 'exhausted',
                'message' => 'وصلت لكوتة رسائل المساعد الذكي هذا الشهر. رقِّ باقتك من الأدمن أو انتظر بداية الشهر التالي.',
                'message_en' => 'Assistant message quota is exhausted for this month. Upgrade the plan or wait until next month.',
            ],
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $rows = SmartAssistantQuotaUsage::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('period')
            ->limit(12)
            ->get(['period', 'used_count'])
            ->map(static fn (SmartAssistantQuotaUsage $row): array => [
                'date' => $row->period,
                'messages' => (int) $row->used_count,
            ])
            ->all();

        return ApiResponse::success([
            'range' => (string) $request->query('range', 'period'),
            'rows' => $rows,
        ]);
    }

    private function usedToday(int $tenantId): int
    {
        $row = SmartAssistantQuotaUsage::query()
            ->where('tenant_id', $tenantId)
            ->where('period', $this->quotaService->period())
            ->first();

        return $row ? (int) $row->used_count : 0;
    }
}

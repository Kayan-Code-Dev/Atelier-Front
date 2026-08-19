<?php

namespace App\Services\Tenant;

use App\Models\Central\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\User;
use App\Support\PlanFeatureGate;
use Carbon\CarbonImmutable;
use DressnMore\SmartAssistantProduct\Application\AiQuotaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantQuotaService
{
    public function __construct(
        private readonly PlanFeatureGate $gate,
        private readonly TenantContext $tenantContext,
        private readonly AiQuotaService $assistantQuota,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function monthlyUsage(?Tenant $tenant = null): array
    {
        $tenant ??= $this->tenantContext->tenant();
        if ($tenant === null) {
            return [
                'period' => $this->periodMeta(),
                'items' => [],
            ];
        }

        $period = $this->periodMeta();
        $from = CarbonImmutable::parse($period['from'])->startOfDay();
        $to = CarbonImmutable::parse($period['to'])->endOfDay();

        $invoiceSale = $this->invoiceCount(Invoice::TYPE_SELL, $from, $to);
        $invoiceRent = $this->invoiceCount(Invoice::TYPE_RENT, $from, $to);
        $invoiceTailoring = $this->invoiceCount(Invoice::TYPE_TAILORING, $from, $to);
        $invoiceTotal = $invoiceSale + $invoiceRent + $invoiceTailoring;

        $items = [
            $this->item(
                key: 'branches',
                label: 'الفروع',
                group: 'capacity',
                used: Branch::query()->count(),
                limit: $this->gate->limit($tenant, 'branches.max'),
                unit: 'فرع',
                period: 'lifetime',
            ),
            $this->item(
                key: 'users',
                label: 'الموظفون (حسابات الدخول)',
                group: 'capacity',
                used: User::query()->count(),
                limit: $this->gate->limit($tenant, 'users.max'),
                unit: 'موظف',
                period: 'lifetime',
            ),
            $this->item(
                key: 'invoices_monthly',
                label: 'إجمالي الفواتير الشهرية',
                group: 'invoices',
                used: $invoiceTotal,
                limit: $this->gate->limit($tenant, 'invoices.monthly.max'),
                unit: 'فاتورة',
                period: 'monthly',
            ),
            $this->item(
                key: 'invoices_sale',
                label: 'فواتير البيع',
                group: 'invoices',
                used: $invoiceSale,
                limit: $this->gate->limit($tenant, 'invoices.sale.max'),
                unit: 'فاتورة',
                period: 'monthly',
            ),
            $this->item(
                key: 'invoices_rent',
                label: 'فواتير الإيجار',
                group: 'invoices',
                used: $invoiceRent,
                limit: $this->gate->limit($tenant, 'invoices.rent.max'),
                unit: 'فاتورة',
                period: 'monthly',
            ),
            $this->item(
                key: 'invoices_tailoring',
                label: 'فواتير التفصيل',
                group: 'invoices',
                used: $invoiceTailoring,
                limit: $this->gate->limit($tenant, 'invoices.tailoring.max'),
                unit: 'فاتورة',
                period: 'monthly',
            ),
            $this->item(
                key: 'ai_chat',
                label: 'رسائل الشات الذكي',
                group: 'chat',
                used: $this->chatMessageCount($from, $to),
                limit: $this->gate->limit($tenant, 'ai_assistant.chat_monthly.max'),
                unit: 'رسالة',
                period: 'monthly',
            ),
            $this->item(
                key: 'smart_assistant_messages',
                label: 'رسائل المساعد الذكي',
                group: 'assistant',
                used: $this->assistantQuota->used($tenant),
                limit: $this->gate->limit($tenant, 'smart_assistant.messages_monthly.max'),
                unit: 'رسالة',
                period: 'monthly',
            ),
        ];

        return [
            'period' => $period,
            'items' => $items,
            'summary' => [
                'monthly_invoice_used' => $invoiceTotal,
                'chat_used' => (int) (collect($items)->firstWhere('key', 'ai_chat')['used'] ?? 0),
                'assistant_used' => (int) (collect($items)->firstWhere('key', 'smart_assistant_messages')['used'] ?? 0),
            ],
        ];
    }

    public function usageForMetric(Tenant $tenant, string $metricKey): int
    {
        $usage = $this->monthlyUsage($tenant);
        $map = [
            'branches.max' => 'branches',
            'users.max' => 'users',
            'invoices.monthly.max' => 'invoices_monthly',
            'invoices.sale.max' => 'invoices_sale',
            'invoices.rent.max' => 'invoices_rent',
            'invoices.tailoring.max' => 'invoices_tailoring',
            'ai_assistant.chat_monthly.max' => 'ai_chat',
            'smart_assistant.messages_monthly.max' => 'smart_assistant_messages',
        ];
        $itemKey = $map[$metricKey] ?? $metricKey;
        foreach ($usage['items'] as $item) {
            if (($item['key'] ?? '') === $itemKey) {
                return (int) ($item['used'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * @return array{label:string,from:string,to:string,month:string,year:int}
     */
    private function periodMeta(): array
    {
        $now = CarbonImmutable::now();

        return [
            'label' => $now->format('m/Y'),
            'from' => $now->startOfMonth()->toDateString(),
            'to' => $now->endOfMonth()->toDateString(),
            'month' => $now->format('Y-m'),
            'year' => (int) $now->year,
        ];
    }

    private function invoiceCount(string $type, CarbonImmutable $from, CarbonImmutable $to): int
    {
        return Invoice::query()
            ->where('type', $type)
            ->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function chatMessageCount(CarbonImmutable $from, CarbonImmutable $to): int
    {
        try {
            if (! Schema::connection('tenant')->hasTable('ai_messages')) {
                return 0;
            }

            return (int) DB::connection('tenant')
                ->table('ai_messages')
                ->where('role', 'user')
                ->whereBetween('created_at', [$from, $to])
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function item(
        string $key,
        string $label,
        string $group,
        int $used,
        int $limit,
        string $unit,
        string $period,
    ): array {
        $unlimited = $limit <= 0;
        $remaining = $unlimited ? null : max(0, $limit - $used);
        $percent = $unlimited || $limit === 0
            ? 0
            : (int) min(100, round(($used / max(1, $limit)) * 100));

        return [
            'key' => $key,
            'label' => $label,
            'group' => $group,
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining,
            'unlimited' => $unlimited,
            'percent' => $percent,
            'unit' => $unit,
            'period' => $period,
            'exhausted' => ! $unlimited && $used >= $limit,
            'warning' => ! $unlimited && $this->warningLevel($used, $limit) !== null,
            'warning_level' => $unlimited ? null : $this->warningLevel($used, $limit),
        ];
    }

    /**
     * Progressive quota UX levels (Free invoices: 10 / 12 / 14 / 15).
     */
    private function warningLevel(int $used, int $limit): ?string
    {
        if ($limit <= 0 || $used <= 0) {
            return null;
        }

        if ($used >= $limit) {
            return 'exhausted';
        }

        // Exact Free-plan invoice ladder when limit is 15.
        if ($limit === 15) {
            if ($used >= 14) {
                return 'urgent';
            }
            if ($used >= 12) {
                return 'warning';
            }
            if ($used >= 10) {
                return 'info';
            }

            return null;
        }

        $remaining = $limit - $used;
        if ($remaining <= max(1, (int) floor($limit * 0.07))) {
            return 'urgent';
        }
        if ($remaining <= max(2, (int) floor($limit * 0.2))) {
            return 'warning';
        }
        if ($used >= (int) floor($limit * 0.66)) {
            return 'info';
        }

        return null;
    }
}

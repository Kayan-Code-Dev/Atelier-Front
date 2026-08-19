<?php

namespace App\Support;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\User;
use App\Services\Tenant\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlanLimitGuard
{
    public function __construct(
        private readonly PlanFeatureGate $gate,
        private readonly TenantContext $tenantContext,
    ) {}

    public function assertCanCreateBranch(): void
    {
        $tenant = $this->tenantContext->tenant();
        if ($tenant === null) {
            return;
        }

        $this->gate->assertUnderLimit(
            $tenant,
            'branches.max',
            Branch::query()->count(),
            'وصلت للحد الأقصى لعدد الفروع في باقتك الحالية. رقِّ إلى الباقة الاحترافية لإضافة فرع جديد.',
        );
    }

    public function assertCanCreateStaffUser(): void
    {
        $tenant = $this->tenantContext->tenant();
        if ($tenant === null) {
            return;
        }

        $this->gate->assertUnderLimit(
            $tenant,
            'users.max',
            User::query()->count(),
            'وصلت للحد الأقصى لعدد الموظفين (حسابات الدخول) في باقتك الحالية. رقِّ باقتك لإضافة موظف جديد.',
        );
    }

    public function assertCanCreateInvoice(string $invoiceType): void
    {
        $tenant = $this->tenantContext->tenant();
        if ($tenant === null) {
            return;
        }

        $from = CarbonImmutable::now()->startOfMonth();
        $to = CarbonImmutable::now()->endOfMonth();

        $monthlyTotal = Invoice::query()
            ->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $monthlyLimit = $this->gate->limit($tenant, 'invoices.monthly.max');
        $this->gate->assertUnderLimit(
            $tenant,
            'invoices.monthly.max',
            $monthlyTotal,
            $monthlyLimit > 0
                ? "وصلت لكوتة الفواتير لهذا الشهر ({$monthlyLimit} فاتورة). رقِّ باقتك للمتابعة بدون انتظار الشهر القادم."
                : 'وصلت لكوتة الفواتير لهذا الشهر. رقِّ باقتك للمتابعة.',
        );

        $featureKey = PlanFeatureCatalog::invoiceLimitKeyForType($invoiceType);
        if ($featureKey === null) {
            return;
        }

        $normalizedType = match ($invoiceType) {
            'sale', 'sell' => Invoice::TYPE_SELL,
            'rent' => Invoice::TYPE_RENT,
            'tailoring' => Invoice::TYPE_TAILORING,
            default => $invoiceType,
        };

        $count = Invoice::query()
            ->where('type', $normalizedType)
            ->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $label = match ($featureKey) {
            'invoices.sale.max' => 'فواتير البيع لهذا الشهر',
            'invoices.rent.max' => 'فواتير الإيجار لهذا الشهر',
            'invoices.tailoring.max' => 'فواتير التفصيل لهذا الشهر',
            default => 'الفواتير لهذا الشهر',
        };

        $this->gate->assertUnderLimit(
            $tenant,
            $featureKey,
            $count,
            "وصلت للكوتة الشهرية لـ{$label}. رقِّ باقتك أو انتظر حتى بداية الشهر التالي.",
        );
    }

    public function assertCanSendAiChatMessage(): void
    {
        $tenant = $this->tenantContext->tenant();
        if ($tenant === null) {
            return;
        }

        $from = CarbonImmutable::now()->startOfMonth();
        $to = CarbonImmutable::now()->endOfMonth();
        $used = 0;

        try {
            if (Schema::connection('tenant')->hasTable('ai_messages')) {
                $used = (int) DB::connection('tenant')
                    ->table('ai_messages')
                    ->where('role', 'user')
                    ->whereBetween('created_at', [$from, $to])
                    ->count();
            }
        } catch (\Throwable) {
            $used = 0;
        }

        $this->gate->assertUnderLimit(
            $tenant,
            'ai_assistant.chat_monthly.max',
            $used,
            'وصلت للكوتة الشهرية لرسائل المستشار الذكي. رقِّ باقتك أو انتظر حتى بداية الشهر التالي.',
        );
    }
}

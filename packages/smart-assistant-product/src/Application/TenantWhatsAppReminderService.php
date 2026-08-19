<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Application;

use App\Models\Central\Tenant;
use App\Models\Tenant\Invoice;
use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantDatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Daily per-tenant customer WhatsApp reminders (pickup / return / congrats).
 * Uses each tenant's own QR session — never the platform number.
 */
final class TenantWhatsAppReminderService
{
    public function __construct(
        private readonly TenantDatabaseManager $tenantDatabaseManager,
        private readonly TenantContext $tenantContext,
        private readonly TenantWhatsAppOutbound $outbound,
        private readonly TenantWhatsAppInvoiceNotifier $notifier,
    ) {}

    /**
     * @return array{tenants:int,pickup:int,return:int,congrats:int,skipped:int}
     */
    public function run(?string $slug = null, ?Carbon $today = null): array
    {
        $today = ($today ?? Carbon::today())->startOfDay();
        $tomorrow = $today->copy()->addDay()->toDateString();
        $yesterday = $today->copy()->subDay()->toDateString();

        $query = Tenant::query()->where('status', 'active')->orderBy('id');
        if ($slug !== null && $slug !== '') {
            $query->where('slug', $slug);
        }

        $totals = ['tenants' => 0, 'pickup' => 0, 'return' => 0, 'congrats' => 0, 'skipped' => 0];

        foreach ($query->get() as $tenant) {
            $tenantId = (int) $tenant->id;
            if ($this->outbound->isPlatformTenant($tenantId)) {
                $totals['skipped']++;
                continue;
            }
            if (! $this->outbound->sessionConnected((string) $tenantId)) {
                $totals['skipped']++;
                continue;
            }

            try {
                $this->tenantContext->setTenant($tenant);
                $this->tenantDatabaseManager->connect($tenant);
                $counts = $this->runForConnectedTenant($tenant, $tomorrow, $yesterday, $today->toDateString());
                $totals['tenants']++;
                $totals['pickup'] += $counts['pickup'];
                $totals['return'] += $counts['return'];
                $totals['congrats'] += $counts['congrats'];
            } catch (Throwable $e) {
                Log::warning('whatsapp.atelier_reminders.tenant_failed', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $totals;
    }

    /**
     * @return array{pickup:int,return:int,congrats:int}
     */
    private function runForConnectedTenant(Tenant $tenant, string $tomorrow, string $yesterday, string $today): array
    {
        $counts = ['pickup' => 0, 'return' => 0, 'congrats' => 0];
        $active = [
            Invoice::STATUS_CONFIRMED,
            Invoice::STATUS_PAID,
            Invoice::STATUS_PARTIALLY_PAID,
            Invoice::STATUS_DELIVERED,
        ];

        Invoice::query()
            ->with(['customer', 'branch', 'items.dress'])
            ->whereNotIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_CANCELLED, Invoice::STATUS_RETURNED])
            ->where(function ($q) use ($tomorrow): void {
                $q->whereDate('delivery_date', $tomorrow)
                    ->orWhere(function ($q2) use ($tomorrow): void {
                        $q2->whereNull('delivery_date')->whereDate('rent_start_date', $tomorrow);
                    });
            })
            ->orderBy('id')
            ->each(function (Invoice $invoice) use ($tenant, &$counts): void {
                if ($this->notifier->notifyPickupReminder($tenant, $invoice)) {
                    $counts['pickup']++;
                }
            });

        Invoice::query()
            ->with(['customer', 'branch', 'items.dress'])
            ->where('type', Invoice::TYPE_RENT)
            ->whereIn('status', $active)
            ->where(function ($q) use ($tomorrow): void {
                $q->whereDate('return_date', $tomorrow)
                    ->orWhere(function ($q2) use ($tomorrow): void {
                        $q2->whereNull('return_date')->whereDate('rent_end_date', $tomorrow);
                    });
            })
            ->orderBy('id')
            ->each(function (Invoice $invoice) use ($tenant, &$counts): void {
                if ($this->notifier->notifyReturnReminder($tenant, $invoice)) {
                    $counts['return']++;
                }
            });

        Invoice::query()
            ->with(['customer', 'branch', 'items.dress'])
            ->where('type', Invoice::TYPE_RENT)
            ->where('status', Invoice::STATUS_RETURNED)
            ->where(function ($q) use ($yesterday, $today): void {
                $q->whereDate('return_date', $yesterday)
                    ->orWhereDate('return_date', $today);
            })
            ->orderBy('id')
            ->each(function (Invoice $invoice) use ($tenant, &$counts): void {
                if ($this->notifier->notifyReturnCongrats($tenant, $invoice)) {
                    $counts['congrats']++;
                }
            });

        return $counts;
    }
}

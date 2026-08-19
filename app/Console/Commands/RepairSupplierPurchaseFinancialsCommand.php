<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Models\Tenant\Account;
use App\Models\Tenant\Cashbox;
use App\Models\Tenant\CashMovement;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\SupplierPayment;
use App\Services\Tenant\CashMovementService;
use App\Services\Tenant\JournalEntryPostingService;
use App\Services\Tenant\PurchaseOrderService;
use App\Services\Tenant\SupplierService;
use App\Services\Tenant\TenantDatabaseManager;
use Illuminate\Console\Command;

class RepairSupplierPurchaseFinancialsCommand extends Command
{
    protected $signature = 'tenants:repair-supplier-purchases {--tenant= : Tenant slug to repair only} {--dry-run : Report only}';

    protected $description = 'Backfill deposit payments, fix doubled PO totals, sync PO paid/remaining, supplier balances, journals and cashbox';

    public function handle(TenantDatabaseManager $tenantDatabaseManager): int
    {
        $slug = $this->option('tenant');
        $dryRun = (bool) $this->option('dry-run');

        $tenants = Tenant::query()
            ->when($slug, fn ($query) => $query->where('slug', $slug))
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');

            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            $this->info("Repairing tenant: {$tenant->slug}");
            $tenantDatabaseManager->connect($tenant);

            $stats = $this->repairTenant($dryRun);
            $this->line(sprintf(
                '  tax_fixed=%d deposits_backfilled=%d pos_synced=%d journals=%d suppliers=%d',
                $stats['tax_fixed'],
                $stats['deposits_backfilled'],
                $stats['pos_synced'],
                $stats['journals_posted'],
                $stats['suppliers_recalculated'],
            ));
        }

        $this->info($dryRun ? 'Dry-run completed.' : 'Repair completed.');

        return self::SUCCESS;
    }

    /**
     * @return array{tax_fixed:int,deposits_backfilled:int,pos_synced:int,journals_posted:int,suppliers_recalculated:int}
     */
    private function repairTenant(bool $dryRun): array
    {
        $purchaseOrderService = app(PurchaseOrderService::class);
        $supplierService = app(SupplierService::class);
        $cashMovementService = app(CashMovementService::class);
        $journalPosting = app(JournalEntryPostingService::class);

        $stats = [
            'tax_fixed' => 0,
            'deposits_backfilled' => 0,
            'pos_synced' => 0,
            'journals_posted' => 0,
            'suppliers_recalculated' => 0,
        ];

        if (! $dryRun) {
            Account::query()->firstOrCreate(
                ['code' => '1300'],
                ['name' => 'المخزون', 'type' => 'asset', 'is_active' => true],
            );
            Account::query()->firstOrCreate(
                ['code' => '2000'],
                ['name' => 'الموردون', 'type' => 'liability', 'is_active' => true],
            );
            Account::query()->firstOrCreate(
                ['code' => '1000'],
                ['name' => 'النقدية', 'type' => 'asset', 'is_active' => true],
            );
        }

        $orders = PurchaseOrder::query()
            ->where('status', '!=', PurchaseOrder::STATUS_CANCELLED)
            ->orderBy('id')
            ->get();

        foreach ($orders as $order) {
            $changed = false;

            $subtotal = round((float) $order->subtotal, 2);
            $tax = round((float) $order->tax, 2);
            if ($tax > 0 && $subtotal > 0 && $tax >= $subtotal) {
                $stats['tax_fixed']++;
                if (! $dryRun) {
                    $order->tax = 0;
                    $order->total = round(max(0, $subtotal - (float) $order->discount), 2);
                    $order->save();
                    $changed = true;
                }
            }

            $paymentsSum = round((float) SupplierPayment::query()
                ->where('purchase_order_id', $order->id)
                ->sum('amount'), 2);
            $deposit = round((float) ($order->deposit_amount ?? 0), 2);
            $missingDeposit = round($deposit - $paymentsSum, 2);

            if ($missingDeposit > 0.009) {
                $stats['deposits_backfilled']++;
                if (! $dryRun) {
                    $cashboxId = $this->resolveCashboxId($order->branch_id ? (int) $order->branch_id : null);
                    $payment = SupplierPayment::query()->create([
                        'supplier_id' => $order->supplier_id,
                        'purchase_order_id' => $order->id,
                        'cashbox_id' => $cashboxId,
                        'amount' => $missingDeposit,
                        'method' => 'cash',
                        'reference' => 'DEP-'.$order->purchase_order_number,
                        'paid_at' => $order->order_date ?? now(),
                        'notes' => 'عربون طلبية شراء (إصلاح تلقائي)',
                        'created_by' => $order->created_by,
                    ]);

                    $existsMovement = CashMovement::query()
                        ->where('reference_type', CashMovement::REFERENCE_SUPPLIER_PAYMENT)
                        ->where('reference_id', $payment->id)
                        ->exists();
                    if (! $existsMovement) {
                        $cashMovementService->recordSupplierPayment($payment, $order->created_by);
                    }

                    $existsJournal = JournalEntry::query()
                        ->where('source_type', JournalEntry::SOURCE_SUPPLIER_PAYMENT)
                        ->where('source_id', $payment->id)
                        ->exists();
                    if (! $existsJournal) {
                        $journalPosting->postFromSupplierPayment($payment, $order->created_by);
                        $stats['journals_posted']++;
                    }
                    $changed = true;
                }
            }

            if (! $dryRun) {
                $purchaseOrderService->syncFinancials($order->refresh());
                $stats['pos_synced']++;
            } elseif ($changed || $missingDeposit > 0.009) {
                $stats['pos_synced']++;
            }
        }

        // Ensure existing payments have journals + cash movements
        if (! $dryRun) {
            $payments = SupplierPayment::query()->orderBy('id')->get();
            foreach ($payments as $payment) {
                $existsJournal = JournalEntry::query()
                    ->where('source_type', JournalEntry::SOURCE_SUPPLIER_PAYMENT)
                    ->where('source_id', $payment->id)
                    ->exists();
                if (! $existsJournal) {
                    $journalPosting->postFromSupplierPayment($payment, $payment->created_by);
                    $stats['journals_posted']++;
                }

                $existsMovement = CashMovement::query()
                    ->where('reference_type', CashMovement::REFERENCE_SUPPLIER_PAYMENT)
                    ->where('reference_id', $payment->id)
                    ->exists();
                if (! $existsMovement && $payment->cashbox_id) {
                    $cashMovementService->recordSupplierPayment($payment, $payment->created_by);
                }
            }
        }

        $suppliers = Supplier::query()->orderBy('id')->get();
        foreach ($suppliers as $supplier) {
            $stats['suppliers_recalculated']++;
            if (! $dryRun) {
                $supplierService->recalculateCurrentBalance($supplier);
            }
        }

        return $stats;
    }

    private function resolveCashboxId(?int $branchId): ?int
    {
        $query = Cashbox::query()->where('is_active', true)->orderBy('id');
        if ($branchId) {
            $match = (clone $query)->where('branch_id', $branchId)->first();
            if ($match) {
                return (int) $match->id;
            }
        }

        return $query->first()?->id;
    }
}

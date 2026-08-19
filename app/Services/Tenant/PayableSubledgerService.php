<?php

namespace App\Services\Tenant;

use App\Accounting\AccountBalanceService;
use App\Accounting\AccountingMoney;
use App\Accounting\JournalSourcePresenter;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\SupplierPayment;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PayableSubledgerService
{
    public const BUCKETS = ['current', '1_30', '31_60', '61_90', '90_plus'];

    public function __construct(private readonly AccountBalanceService $balances) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function index(array $filters = []): array
    {
        $asOf = $this->asOf($filters);
        $rows = [];
        $totals = $this->emptyBuckets();
        $billed = 0.0;
        $paid = 0.0;
        $outstanding = 0.0;
        $overdue = 0.0;

        foreach ($this->suppliers($filters) as $supplier) {
            $row = $this->supplierRow($supplier, $asOf, $filters);
            if (AccountingMoney::isZero($row['billed']) && AccountingMoney::isZero($row['outstanding'])) {
                continue;
            }
            $rows[] = $row;
            $billed = AccountingMoney::toFloat(AccountingMoney::add($billed, $row['billed']));
            $paid = AccountingMoney::toFloat(AccountingMoney::add($paid, $row['paid']));
            $outstanding = AccountingMoney::toFloat(AccountingMoney::add($outstanding, $row['outstanding']));
            $overdue = AccountingMoney::toFloat(AccountingMoney::add($overdue, $row['overdue']));
            foreach (self::BUCKETS as $bucket) {
                $totals[$bucket] = AccountingMoney::toFloat(AccountingMoney::add($totals[$bucket], $row['aging'][$bucket]));
            }
        }

        $gl = $this->balances->balanceByCode('2000', $asOf, $this->branchId($filters));
        $difference = AccountingMoney::toFloat(AccountingMoney::sub($outstanding, $gl));

        return [
            'as_of' => $asOf,
            'suppliers' => $rows,
            'totals' => [
                'billed' => $billed,
                'paid' => $paid,
                'outstanding' => $outstanding,
                'overdue' => $overdue,
                'aging' => $totals,
            ],
            'gl_payables' => $gl,
            'subledger_outstanding' => $outstanding,
            'difference' => $difference,
            'reconciles_to_gl' => AccountingMoney::isZero($difference),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function statement(int $supplierId, array $filters = []): array
    {
        $supplier = Supplier::query()->findOrFail($supplierId);
        $from = (string) ($filters['date_from'] ?? Carbon::parse($this->asOf($filters))->startOfYear()->toDateString());
        $to = $this->asOf($filters);
        $branchId = $this->branchId($filters);

        $orders = $this->orderQuery($branchId)->where('supplier_id', $supplier->id)->orderBy('id')->get();
        $opening = AccountingMoney::toFloat($supplier->opening_balance);
        $lines = [];

        foreach ($orders as $order) {
            $date = optional($order->order_date)->toDateString() ?? optional($order->created_at)->toDateString();
            if ($date && $date < $from) {
                $opening = AccountingMoney::toFloat(AccountingMoney::add($opening, (float) $order->remaining_amount));
                continue;
            }
            if ($date && $date > $to) {
                continue;
            }
            $source = $this->orderSource($order);
            $lines[] = [
                'date' => $date,
                'type' => 'bill',
                'label' => 'فاتورة مورد '.$order->purchase_order_number,
                'debit' => 0,
                'credit' => AccountingMoney::toFloat($order->total),
                'source_type' => $source['source_type'],
                'source_id' => $source['source_id'],
                'journal_entry_id' => $source['journal_entry_id'],
                'path' => $source['path'],
            ];

            $payments = SupplierPayment::query()
                ->where('purchase_order_id', $order->id)
                ->when($from, fn ($query) => $query->whereDate('paid_at', '>=', $from))
                ->when($to, fn ($query) => $query->whereDate('paid_at', '<=', $to))
                ->get();
            foreach ($payments as $payment) {
                $paySource = $this->paymentSource($payment);
                $lines[] = [
                    'date' => optional($payment->paid_at)->toDateString(),
                    'type' => 'payment',
                    'label' => 'دفعة مورد '.$order->purchase_order_number,
                    'debit' => AccountingMoney::toFloat($payment->amount),
                    'credit' => 0,
                    'source_type' => $paySource['source_type'],
                    'source_id' => $paySource['source_id'],
                    'journal_entry_id' => $paySource['journal_entry_id'],
                    'path' => $paySource['path'],
                ];
            }

            if ($order->is_returned) {
                $lines[] = [
                    'date' => optional($order->returned_at)->toDateString() ?? $date,
                    'type' => 'return',
                    'label' => 'مرتجع مورد '.$order->purchase_order_number,
                    'debit' => AccountingMoney::toFloat($order->total),
                    'credit' => 0,
                    'source_type' => JournalEntry::SOURCE_PURCHASE,
                    'source_id' => $order->id,
                    'journal_entry_id' => $source['journal_entry_id'],
                    'path' => $source['path'],
                ];
            }
        }

        usort($lines, fn (array $a, array $b): int => strcmp((string) $a['date'], (string) $b['date']));
        $balance = $opening;
        foreach ($lines as &$line) {
            $balance = AccountingMoney::toFloat(AccountingMoney::add($balance, AccountingMoney::sub($line['credit'], $line['debit'])));
            $line['balance'] = $balance;
        }
        unset($line);

        $row = $this->supplierRow($supplier, $to, $filters);

        return [
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone' => $supplier->phone,
            ],
            'opening_balance' => $opening,
            'lines' => $lines,
            'closing_balance' => $balance,
            'billed' => $row['billed'],
            'paid' => $row['paid'],
            'outstanding' => $row['outstanding'],
            'overdue' => $row['overdue'],
            'aging' => $row['aging'],
            'note' => 'كشف المورد طبقة فرعية مشتقة من أوامر الشراء والدفعات، والحساب المالي يبقى في الأستاذ العام 2000.',
        ];
    }

    /**
     * @return array<string, float>
     */
    public function agingTotals(array $filters = []): array
    {
        return $this->index($filters)['totals']['aging'];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function mismatch(array $filters = []): ?array
    {
        $index = $this->index($filters);
        if ($index['reconciles_to_gl']) {
            return null;
        }

        return [
            'subledger' => $index['subledger_outstanding'],
            'gl' => $index['gl_payables'],
            'difference' => $index['difference'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function supplierRow(Supplier $supplier, string $asOf, array $filters): array
    {
        $orders = $this->orderQuery($this->branchId($filters))->where('supplier_id', $supplier->id)->get();
        $billed = 0.0;
        $paid = 0.0;
        $outstanding = 0.0;
        $overdue = 0.0;
        $aging = $this->emptyBuckets();

        foreach ($orders as $order) {
            $billed = AccountingMoney::toFloat(AccountingMoney::add($billed, (float) $order->total));
            $paid = AccountingMoney::toFloat(AccountingMoney::add($paid, (float) $order->paid_amount));
            $remaining = AccountingMoney::toFloat($order->remaining_amount);
            if ($remaining <= 0) {
                continue;
            }
            $outstanding = AccountingMoney::toFloat(AccountingMoney::add($outstanding, $remaining));
            $bucket = $this->bucket($this->daysPastDue($order, $asOf));
            $aging[$bucket] = AccountingMoney::toFloat(AccountingMoney::add($aging[$bucket], $remaining));
            if ($bucket !== 'current') {
                $overdue = AccountingMoney::toFloat(AccountingMoney::add($overdue, $remaining));
            }
        }

        return [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'billed' => $billed,
            'paid' => $paid,
            'outstanding' => $outstanding,
            'overdue' => $overdue,
            'aging' => $aging,
            'path' => '/accounting/payables/'.$supplier->id,
        ];
    }

    private function orderQuery(?int $branchId): Builder
    {
        return PurchaseOrder::query()
            ->whereNotIn('status', [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_CANCELLED])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Support\Collection<int, Supplier>
     */
    private function suppliers(array $filters)
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return Supplier::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $wildcard = '%'.mb_strtolower($search).'%';
                $query->where(function (Builder $builder) use ($wildcard): void {
                    $builder->whereRaw('LOWER(name) LIKE ?', [$wildcard])
                        ->orWhereRaw('LOWER(phone) LIKE ?', [$wildcard]);
                });
            })
            ->orderBy('name')
            ->get();
    }

    private function daysPastDue(PurchaseOrder $order, string $asOf): int
    {
        $due = $order->order_date ?? $order->received_at ?? $order->created_at;
        $dueDate = Carbon::parse($due)->startOfDay();
        $asOfDate = Carbon::parse($asOf)->startOfDay();
        if ($asOfDate->lte($dueDate)) {
            return 0;
        }

        return (int) $dueDate->diffInDays($asOfDate);
    }

    private function bucket(int $days): string
    {
        if ($days <= 0) {
            return 'current';
        }
        if ($days <= 30) {
            return '1_30';
        }
        if ($days <= 60) {
            return '31_60';
        }
        if ($days <= 90) {
            return '61_90';
        }

        return '90_plus';
    }

    /**
     * @return array<string, float>
     */
    private function emptyBuckets(): array
    {
        return [
            'current' => 0.0,
            '1_30' => 0.0,
            '31_60' => 0.0,
            '61_90' => 0.0,
            '90_plus' => 0.0,
        ];
    }

    /**
     * @return array{source_type: string, source_id: int, journal_entry_id: int|null, path: string}
     */
    private function orderSource(PurchaseOrder $order): array
    {
        $entry = JournalEntry::query()
            ->whereIn('source_type', [JournalEntry::SOURCE_PURCHASE_ORDER, JournalEntry::SOURCE_PURCHASE])
            ->where('source_id', $order->id)
            ->whereIn('status', JournalEntry::postedStatuses())
            ->first();

        return [
            'source_type' => JournalEntry::SOURCE_PURCHASE_ORDER,
            'source_id' => $order->id,
            'journal_entry_id' => $entry?->id,
            'path' => JournalSourcePresenter::url(JournalEntry::SOURCE_PURCHASE_ORDER, $order->id) ?? '/purchase-orders/'.$order->id,
        ];
    }

    /**
     * @return array{source_type: string, source_id: int, journal_entry_id: int|null, path: string}
     */
    private function paymentSource(SupplierPayment $payment): array
    {
        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_SUPPLIER_PAYMENT)
            ->where('source_id', $payment->id)
            ->whereIn('status', JournalEntry::postedStatuses())
            ->first();

        return [
            'source_type' => JournalEntry::SOURCE_SUPPLIER_PAYMENT,
            'source_id' => $payment->id,
            'journal_entry_id' => $entry?->id,
            'path' => JournalSourcePresenter::url(JournalEntry::SOURCE_SUPPLIER_PAYMENT, $payment->id) ?? '/supplier-payments/'.$payment->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function asOf(array $filters): string
    {
        return (string) ($filters['date_to'] ?? $filters['date'] ?? now()->toDateString());
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function branchId(array $filters): ?int
    {
        if (($filters['branch_id'] ?? '') !== '' && $filters['branch_id'] !== null) {
            return (int) $filters['branch_id'];
        }
        $user = $filters['user'] ?? null;
        if ($user instanceof User && ! $user->isOwner() && $user->branch_id) {
            return (int) $user->branch_id;
        }

        return null;
    }
}

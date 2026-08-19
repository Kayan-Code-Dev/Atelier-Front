<?php

namespace App\Services\Tenant;

use App\Accounting\AccountBalanceService;
use App\Accounting\AccountingMoney;
use App\Accounting\JournalSourcePresenter;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoicePayment;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ReceivableSubledgerService
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
        $invoiced = 0.0;
        $paid = 0.0;
        $outstanding = 0.0;
        $overdue = 0.0;

        foreach ($this->customers($filters) as $customer) {
            $row = $this->customerRow($customer, $asOf, $filters);
            if (AccountingMoney::isZero($row['invoiced']) && AccountingMoney::isZero($row['outstanding'])) {
                continue;
            }
            $rows[] = $row;
            $invoiced = AccountingMoney::toFloat(AccountingMoney::add($invoiced, $row['invoiced']));
            $paid = AccountingMoney::toFloat(AccountingMoney::add($paid, $row['paid']));
            $outstanding = AccountingMoney::toFloat(AccountingMoney::add($outstanding, $row['outstanding']));
            $overdue = AccountingMoney::toFloat(AccountingMoney::add($overdue, $row['overdue']));
            foreach (self::BUCKETS as $bucket) {
                $totals[$bucket] = AccountingMoney::toFloat(AccountingMoney::add($totals[$bucket], $row['aging'][$bucket]));
            }
        }

        $gl = $this->balances->balanceByCode('1200', $asOf, $this->branchId($filters));
        $difference = AccountingMoney::toFloat(AccountingMoney::sub($outstanding, $gl));

        return [
            'as_of' => $asOf,
            'customers' => $rows,
            'totals' => [
                'invoiced' => $invoiced,
                'paid' => $paid,
                'outstanding' => $outstanding,
                'overdue' => $overdue,
                'aging' => $totals,
            ],
            'gl_receivables' => $gl,
            'subledger_outstanding' => $outstanding,
            'difference' => $difference,
            'reconciles_to_gl' => AccountingMoney::isZero($difference),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function statement(int $customerId, array $filters = []): array
    {
        $customer = Customer::query()->findOrFail($customerId);
        $from = (string) ($filters['date_from'] ?? Carbon::parse($this->asOf($filters))->startOfYear()->toDateString());
        $to = $this->asOf($filters);
        $branchId = $this->branchId($filters);

        $invoices = $this->invoiceQuery($branchId)
            ->where('customer_id', $customer->id)
            ->orderBy('id')
            ->get();

        $opening = 0.0;
        $lines = [];
        foreach ($invoices as $invoice) {
            $date = $this->invoiceDate($invoice);
            if ($date < $from) {
                $opening = AccountingMoney::toFloat(AccountingMoney::add($opening, (float) $invoice->remaining_amount));
                continue;
            }
            if ($date > $to) {
                continue;
            }
            $source = $this->invoiceSource($invoice);
            $lines[] = [
                'date' => $date,
                'type' => 'invoice',
                'label' => 'فاتورة '.$invoice->invoice_number,
                'debit' => AccountingMoney::toFloat($invoice->total),
                'credit' => 0,
                'source_type' => $source['source_type'],
                'source_id' => $source['source_id'],
                'journal_entry_id' => $source['journal_entry_id'],
                'path' => $source['path'],
            ];

            $payments = InvoicePayment::query()
                ->where('invoice_id', $invoice->id)
                ->where('status', InvoicePayment::STATUS_PAID)
                ->whereNotNull('paid_at')
                ->whereDate('paid_at', '>=', $from)
                ->whereDate('paid_at', '<=', $to)
                ->get();
            foreach ($payments as $payment) {
                $paySource = $this->paymentSource($payment);
                $lines[] = [
                    'date' => optional($payment->paid_at)->toDateString(),
                    'type' => 'payment',
                    'label' => 'دفعة '.$invoice->invoice_number,
                    'debit' => 0,
                    'credit' => AccountingMoney::toFloat($payment->amount),
                    'source_type' => $paySource['source_type'],
                    'source_id' => $paySource['source_id'],
                    'journal_entry_id' => $paySource['journal_entry_id'],
                    'path' => $paySource['path'],
                ];
            }

            if (in_array($invoice->status, [Invoice::STATUS_RETURNED, Invoice::STATUS_CANCELLED], true)
                && AccountingMoney::toFloat($invoice->total) > 0) {
                $lines[] = [
                    'date' => $date,
                    'type' => 'return',
                    'label' => 'مرتجع '.$invoice->invoice_number,
                    'debit' => 0,
                    'credit' => AccountingMoney::toFloat($invoice->total),
                    'source_type' => JournalEntry::SOURCE_RETURN,
                    'source_id' => $invoice->id,
                    'journal_entry_id' => $source['journal_entry_id'],
                    'path' => $source['path'],
                ];
            }
        }

        usort($lines, fn (array $a, array $b): int => strcmp((string) $a['date'], (string) $b['date']));
        $balance = $opening;
        foreach ($lines as &$line) {
            $balance = AccountingMoney::toFloat(AccountingMoney::add($balance, AccountingMoney::sub($line['debit'], $line['credit'])));
            $line['balance'] = $balance;
        }
        unset($line);

        $row = $this->customerRow($customer, $to, $filters);

        return [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ],
            'opening_balance' => $opening,
            'lines' => $lines,
            'closing_balance' => $balance,
            'invoiced' => $row['invoiced'],
            'paid' => $row['paid'],
            'outstanding' => $row['outstanding'],
            'overdue' => $row['overdue'],
            'aging' => $row['aging'],
            'note' => 'كشف العميل طبقة فرعية مشتقة من الفواتير والدفعات، والحساب المالي يبقى في الأستاذ العام 1200.',
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
            'gl' => $index['gl_receivables'],
            'difference' => $index['difference'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function customerRow(Customer $customer, string $asOf, array $filters): array
    {
        $invoices = $this->invoiceQuery($this->branchId($filters))
            ->where('customer_id', $customer->id)
            ->get();

        $invoiced = 0.0;
        $paid = 0.0;
        $outstanding = 0.0;
        $overdue = 0.0;
        $aging = $this->emptyBuckets();

        foreach ($invoices as $invoice) {
            $invoiced = AccountingMoney::toFloat(AccountingMoney::add($invoiced, (float) $invoice->total));
            $paid = AccountingMoney::toFloat(AccountingMoney::add($paid, (float) $invoice->paid_amount));
            $remaining = AccountingMoney::toFloat($invoice->remaining_amount);
            if ($remaining <= 0) {
                continue;
            }
            $outstanding = AccountingMoney::toFloat(AccountingMoney::add($outstanding, $remaining));
            $bucket = $this->bucket($this->daysPastDue($invoice, $asOf));
            $aging[$bucket] = AccountingMoney::toFloat(AccountingMoney::add($aging[$bucket], $remaining));
            if ($bucket !== 'current') {
                $overdue = AccountingMoney::toFloat(AccountingMoney::add($overdue, $remaining));
            }
        }

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'invoiced' => $invoiced,
            'paid' => $paid,
            'outstanding' => $outstanding,
            'overdue' => $overdue,
            'aging' => $aging,
            'path' => '/accounting/receivables/'.$customer->id,
        ];
    }

    private function invoiceQuery(?int $branchId): Builder
    {
        return Invoice::query()
            ->whereNotIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_CANCELLED])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Support\Collection<int, Customer>
     */
    private function customers(array $filters)
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return Customer::query()
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

    private function invoiceDate(Invoice $invoice): string
    {
        return optional($invoice->created_at)->toDateString()
            ?? now()->toDateString();
    }

    private function daysPastDue(Invoice $invoice, string $asOf): int
    {
        $due = $invoice->tailoring_due_date
            ?? $invoice->rent_end_date
            ?? $invoice->delivery_date
            ?? $invoice->created_at;
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
    private function invoiceSource(Invoice $invoice): array
    {
        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_INVOICE)
            ->where('source_id', $invoice->id)
            ->whereIn('status', JournalEntry::postedStatuses())
            ->first();

        return [
            'source_type' => JournalEntry::SOURCE_INVOICE,
            'source_id' => $invoice->id,
            'journal_entry_id' => $entry?->id,
            'path' => JournalSourcePresenter::url(JournalEntry::SOURCE_INVOICE, $invoice->id) ?? '/sales/invoices/'.$invoice->id,
        ];
    }

    /**
     * @return array{source_type: string, source_id: int, journal_entry_id: int|null, path: string}
     */
    private function paymentSource(InvoicePayment $payment): array
    {
        $entry = JournalEntry::query()
            ->where('source_type', JournalEntry::SOURCE_PAYMENT)
            ->where('source_id', $payment->id)
            ->whereIn('status', JournalEntry::postedStatuses())
            ->first();

        return [
            'source_type' => JournalEntry::SOURCE_PAYMENT,
            'source_id' => $payment->id,
            'journal_entry_id' => $entry?->id,
            'path' => JournalSourcePresenter::url(JournalEntry::SOURCE_PAYMENT, $payment->id) ?? '/payments/'.$payment->id,
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

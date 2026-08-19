<?php

namespace App\Services\Tenant;

use App\Accounting\AccountBalanceService;
use App\Accounting\AccountingMoney;
use App\Accounting\GeneralLedgerService;
use App\Models\Tenant\Account;
use App\Models\Tenant\AccountingPeriod;
use App\Models\Tenant\Cashbox;
use App\Models\Tenant\Expense;
use App\Models\Tenant\InvoicePayment;
use App\Models\Tenant\JournalEntry;
use App\Support\ReportDateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class AccountingService
{
    /** @var array<string, array{id: int, name: string, code: string}> */
    public const TYPE_META = [
        'asset' => ['id' => 1, 'name' => 'الأصول', 'code' => 'asset'],
        'liability' => ['id' => 2, 'name' => 'الخصوم', 'code' => 'liability'],
        'equity' => ['id' => 3, 'name' => 'حقوق الملكية', 'code' => 'equity'],
        'revenue' => ['id' => 4, 'name' => 'الإيرادات', 'code' => 'revenue'],
        'expense' => ['id' => 5, 'name' => 'المصروفات', 'code' => 'expense'],
    ];

    public function __construct(
        private readonly AccountBalanceService $accountBalances,
        private readonly GeneralLedgerService $generalLedger,
        private readonly FinancialStatementService $statements,
        private readonly ReceivableSubledgerService $receivables,
        private readonly PayableSubledgerService $payables,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters): array
    {
        $period = ReportDateRange::resolve($filters);
        $asOf = $period['to'];
        $branchId = $this->branchId($filters);

        $asOfBalances = $this->accountBalances->balancesByAccount(null, $asOf, $branchId);
        $periodBalances = $this->accountBalances->balancesByAccount($period['from'], $asOf, $branchId);

        $totalAssets = $this->accountBalances->sumType($asOfBalances, 'asset');
        $totalLiabilities = $this->accountBalances->sumType($asOfBalances, 'liability');
        $totalEquity = $this->accountBalances->sumType($asOfBalances, 'equity');
        $totalRevenue = $this->accountBalances->sumType($periodBalances, 'revenue');
        $totalExpenses = $this->accountBalances->sumType($periodBalances, 'expense');
        $netIncome = AccountingMoney::toFloat(AccountingMoney::sub($totalRevenue, $totalExpenses));

        $cash = $this->cashSnapshot($filters);

        $cashCodes = ['1000', '1010'];
        $cashMovement = $this->accountBalances->periodMovementByCodes($cashCodes, $period['from'], $asOf, $branchId);
        $cashBalance = $this->accountBalances->sumCodes($cashCodes, $asOf, $branchId);
        $accountsReceivable = $this->accountBalances->balanceByCode('1200', $asOf, $branchId);
        $accountsPayable = $this->accountBalances->balanceByCode('2000', $asOf, $branchId);
        $grossFixedAssets = $this->accountBalances->sumCodes(['1410', '1420', '1430', '1440', '1450', '1460'], $asOf, $branchId);
        $accumulatedDepreciation = $this->accountBalances->balanceByCode('1490', $asOf, $branchId);
        $periodDepreciation = $this->accountBalances->periodMovementByCodes(['5300'], $period['from'], $asOf, $branchId)['debit'];

        return [
            'accounts' => Account::query()->where('allow_posting', true)->count(),
            'journal_entries' => JournalEntry::query()->whereIn('status', JournalEntry::postedStatuses())->count(),
            'treasury_accounts' => Cashbox::query()->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))->count(),
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => AccountingMoney::toFloat(AccountingMoney::add($totalEquity, $this->accountBalances->retainedEarnings($asOfBalances))),
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
            'period' => $period,
            'total_income' => $totalRevenue,
            'net_change' => $netIncome,
            'net_profit' => $netIncome,
            'cash_income' => $cash['total_income'],
            'cash_expenses' => $cash['total_expenses'],
            'cashbox_balances' => $cash['cashbox_balances'],
            'cash_balance' => $cashBalance,
            'accounts_receivable' => $accountsReceivable,
            'accounts_payable' => $accountsPayable,
            'receipts' => $cashMovement['debit'],
            'payments' => $cashMovement['credit'],
            'gross_fixed_assets' => $grossFixedAssets,
            'accumulated_depreciation' => $accumulatedDepreciation,
            'net_fixed_assets' => AccountingMoney::toFloat(AccountingMoney::sub($grossFixedAssets, $accumulatedDepreciation)),
            'current_period_depreciation' => $periodDepreciation,
            'capital' => $this->accountBalances->balanceByCode('3000', $asOf, $branchId),
            'owner_drawings' => $this->accountBalances->balanceByCode('3100', $asOf, $branchId),
            'retained_earnings' => $this->accountBalances->retainedEarnings($asOfBalances),
            'supplier_payables' => $accountsPayable,
            'loans' => $this->accountBalances->balanceByCode('2200', $asOf, $branchId),
            'other_liabilities' => $this->accountBalances->balanceByCode('2290', $asOf, $branchId),
            'gross_profit' => $totalRevenue,
            'negative_cash' => $cashBalance < 0,
            'negative_cash_message' => $cashBalance < 0 ? 'رصيد نقدي سالب يحتاج مراجعة' : null,
            'receivables_aging' => $this->receivables->agingTotals($filters),
            'payables_aging' => $this->payables->agingTotals($filters),
            'financial_position' => [
                'assets' => $totalAssets,
                'liabilities' => $totalLiabilities,
                'equity' => AccountingMoney::toFloat(AccountingMoney::add($totalEquity, $this->accountBalances->retainedEarnings($asOfBalances))),
            ],
            'performance' => [
                'revenue' => $totalRevenue,
                'expenses' => $totalExpenses,
                'gross_profit' => $totalRevenue,
                'net_profit' => $netIncome,
            ],
            'liquidity' => [
                'cash' => $cashBalance,
                'receivables' => $accountsReceivable,
                'payables' => $accountsPayable,
            ],
            'controls' => $this->controlSnapshot(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function accountsTree(array $filters = []): array
    {
        $period = ReportDateRange::resolve($filters + ['period' => $filters['period'] ?? 'year']);
        $asOf = trim((string) ($filters['date'] ?? $period['to']));
        $accounts = $this->accountBalances->balancesByAccount(null, $asOf, $this->branchId($filters));

        $tree = [];
        foreach (self::TYPE_META as $type => $meta) {
            $children = $accounts
                ->where('type', $type)
                ->filter(fn (array $account): bool => ($account['allow_posting'] ?? true) === true)
                ->values()
                ->map(fn (array $account): array => [
                    'id' => $account['id'],
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'current_balance' => $account['current_balance'],
                    'status' => $account['is_active'] ? 'active' : 'inactive',
                ])
                ->all();

            $tree[] = [
                'id' => -1 * $meta['id'],
                'code' => (string) $meta['id'],
                'name' => $meta['name'],
                'type' => $meta,
                'current_balance' => round(array_sum(array_column($children, 'current_balance')), 2),
                'status' => 'active',
                'children' => $children,
            ];
        }

        return $tree;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function balanceSheet(array $filters): array
    {
        return $this->statements->balanceSheet($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function incomeStatement(array $filters): array
    {
        return $this->statements->incomeStatement($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function ledger(array $filters): array
    {
        $accountId = (int) ($filters['account_id'] ?? 0);
        if ($accountId > 0) {
            return $this->generalLedger->statement($accountId, $filters)['lines'];
        }

        return $this->cashLedger($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function ledgerStatement(array $filters): array
    {
        return $this->generalLedger->statement((int) $filters['account_id'], $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function generalLedger(array $filters): array
    {
        $period = ReportDateRange::resolve($filters);

        return $this->accountBalances->trialBalance(
            $period['from'],
            $period['to'],
            $this->branchId($filters),
            isset($filters['account_id']) && $filters['account_id'] !== '' ? (int) $filters['account_id'] : null,
            isset($filters['account_type']) && $filters['account_type'] !== '' && $filters['account_type'] !== 'all'
                ? (string) $filters['account_type']
                : null,
            isset($filters['source_type']) && $filters['source_type'] !== '' && $filters['source_type'] !== 'all'
                ? (string) $filters['source_type']
                : null,
        )->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{data: list<array<string, mixed>>}
     */
    public function treasuryAccounts(array $filters = []): array
    {
        $branchId = $this->branchId($filters);

        $rows = Cashbox::query()
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'current_balance', 'is_active'])
            ->map(fn (Cashbox $cashbox): array => [
                'id' => $cashbox->id,
                'name' => $cashbox->name,
                'type' => 'cashbox',
                'current_balance' => round((float) $cashbox->current_balance, 2),
                'currency' => 'LYD',
                'is_active' => (bool) $cashbox->is_active,
            ])
            ->values()
            ->all();

        return ['data' => $rows];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total_income: float, total_expenses: float, cashbox_balances: list<array<string, mixed>>}
     */
    private function cashSnapshot(array $filters): array
    {
        $period = ReportDateRange::resolve($filters);
        $branchId = $this->branchId($filters);

        $incomeQuery = InvoicePayment::query()
            ->where('status', InvoicePayment::STATUS_PAID)
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', '>=', $period['from'])
            ->whereDate('paid_at', '<=', $period['to']);

        if ($branchId) {
            $incomeQuery->whereHas('invoice', fn (Builder $query) => $query->where('branch_id', $branchId));
        }

        $expenseQuery = Expense::query()
            ->where('status', Expense::STATUS_PAID)
            ->whereDate('expense_date', '>=', $period['from'])
            ->whereDate('expense_date', '<=', $period['to']);

        if ($branchId) {
            $expenseQuery->where('branch_id', $branchId);
        }

        $cashboxBalances = Cashbox::query()
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['name', 'current_balance'])
            ->map(fn (Cashbox $cashbox): array => [
                'name' => $cashbox->name,
                'balance' => round((float) $cashbox->current_balance, 2),
            ])
            ->values()
            ->all();

        return [
            'total_income' => round((float) (clone $incomeQuery)->sum('amount'), 2),
            'total_expenses' => round((float) (clone $expenseQuery)->sum('amount'), 2),
            'cashbox_balances' => $cashboxBalances,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function cashLedger(array $filters): array
    {
        $period = ReportDateRange::resolve($filters);
        $branchId = $this->branchId($filters);
        $search = trim((string) ($filters['search'] ?? ''));

        $entries = [];

        $payments = InvoicePayment::query()
            ->with('invoice:id,invoice_number,branch_id')
            ->where('status', InvoicePayment::STATUS_PAID)
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', '>=', $period['from'])
            ->whereDate('paid_at', '<=', $period['to'])
            ->when($branchId, fn (Builder $query) => $query->whereHas(
                'invoice',
                fn (Builder $invoiceQuery) => $invoiceQuery->where('branch_id', $branchId)
            ))
            ->latest('paid_at')
            ->get();

        foreach ($payments as $payment) {
            $entries[] = [
                'id' => $payment->id,
                'date' => $payment->paid_at?->toDateString() ?? '',
                'type' => 'credit',
                'reference' => $payment->invoice?->invoice_number ?? "PAY-{$payment->id}",
                'description' => 'Invoice payment',
                'debit' => 0,
                'credit' => round((float) $payment->amount, 2),
                'balance' => 0,
            ];
        }

        $expenses = Expense::query()
            ->where('status', Expense::STATUS_PAID)
            ->whereDate('expense_date', '>=', $period['from'])
            ->whereDate('expense_date', '<=', $period['to'])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->latest('expense_date')
            ->get();

        foreach ($expenses as $expense) {
            $entries[] = [
                'id' => 100000 + $expense->id,
                'date' => $expense->expense_date?->toDateString() ?? '',
                'type' => 'debit',
                'reference' => $expense->reference_number ?? "EXP-{$expense->id}",
                'description' => $expense->description ?? $expense->vendor ?? 'Expense',
                'debit' => round((float) $expense->amount, 2),
                'credit' => 0,
                'balance' => 0,
            ];
        }

        usort($entries, fn (array $a, array $b): int => strcmp($b['date'], $a['date']));

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $entries = array_values(array_filter(
                $entries,
                fn (array $entry): bool => str_contains(mb_strtolower($entry['reference']), $needle)
                    || str_contains(mb_strtolower($entry['description']), $needle)
            ));
        }

        $runningBalance = 0.0;
        foreach (array_reverse($entries) as $index => $entry) {
            $runningBalance += $entry['credit'] - $entry['debit'];
            $entries[count($entries) - 1 - $index]['balance'] = round($runningBalance, 2);
        }

        return $entries;
    }

    /**
     * @return array<string, mixed>
     */
    private function controlSnapshot(): array
    {
        $drafts = JournalEntry::query()->where('status', JournalEntry::STATUS_DRAFT)->count();
        $posted = JournalEntry::query()->whereIn('status', JournalEntry::postedStatuses())->count();
        $exceptions = JournalEntry::query()
            ->whereIn('status', JournalEntry::postedStatuses())
            ->where(function ($query): void {
                $query->where('is_balanced', false)->orWhereColumn('total_debit', '!=', 'total_credit');
            })
            ->count();

        $current = null;
        if (Schema::connection('tenant')->hasTable('accounting_periods')) {
            $period = AccountingPeriod::query()
                ->whereDate('starts_on', '<=', now()->toDateString())
                ->whereDate('ends_on', '>=', now()->toDateString())
                ->first();
            if ($period instanceof AccountingPeriod) {
                $current = [
                    'id' => $period->id,
                    'name' => $period->name,
                    'status' => $period->status ?: ($period->is_closed ? 'closed' : 'open'),
                ];
            }
        }

        return [
            'posted_entries' => $posted,
            'draft_entries' => $drafts,
            'exceptions' => $exceptions,
            'current_period' => $current,
        ];
    }

    private function branchId(array $filters): ?int
    {
        $branchId = $filters['branch_id'] ?? null;
        if ($branchId === null || $branchId === '') {
            return null;
        }

        return (int) $branchId;
    }
}

<?php

namespace App\Services\Tenant;

use App\Accounting\AccountingMoney;
use App\Models\Tenant\AccountingPeriod;
use App\Models\Tenant\BankAccount;
use App\Models\Tenant\BankReconciliation;
use App\Models\Tenant\FixedAssetDepreciationEntry;
use App\Models\Tenant\FixedAssetDepreciationRun;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\JournalEntryLine;
use Illuminate\Support\Facades\Schema;

class AccountingControlService
{
    public function __construct(
        private readonly FinancialStatementService $statements,
        private readonly BankReconciliationService $reconciliations,
        private readonly ReceivableSubledgerService $receivables,
        private readonly PayableSubledgerService $payables,
        private readonly \App\Accounting\AccountBalanceService $balances,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters = []): array
    {
        $checks = $this->checks($filters);
        $unposted = $this->unposted($filters);
        $exceptions = $this->exceptions($filters);
        $failed = collect($checks)->filter(fn (array $check): bool => ! $check['ok'])->count();

        return [
            'healthy' => $failed === 0 && $exceptions === [],
            'failed_checks' => $failed,
            'checks' => array_values($checks),
            'unposted' => $unposted,
            'exceptions' => $exceptions,
            'current_period' => $this->currentPeriod(),
            'treasury_reconciliation' => $this->reconciliations->controlCenter($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, array<string, mixed>>
     */
    public function checks(array $filters = []): array
    {
        $trial = $this->statements->trialBalance($filters + ['include_zeros' => false]);
        $sheet = $this->statements->balanceSheet($filters);
        $treasury = $this->statements->treasuryReconciliationCheck($filters);
        $assets = $this->statements->assetsReconciliationCheck($filters);

        $postedDebit = (float) JournalEntry::query()->whereIn('status', JournalEntry::postedStatuses())->sum('total_debit');
        $postedCredit = (float) JournalEntry::query()->whereIn('status', JournalEntry::postedStatuses())->sum('total_credit');
        $debitsEqualCredits = AccountingMoney::isZero(AccountingMoney::sub($postedDebit, $postedCredit));

        $orphan = $this->orphanLineCount();
        $duplicateDep = $this->duplicateDepreciationCount();
        $unpostedCritical = $this->unposted($filters)['total'];

        return [
            'debits_equal_credits' => $this->check(
                'debits_equal_credits',
                'Debits = Credits',
                $debitsEqualCredits,
                $debitsEqualCredits ? null : 'إجمالي المدين لا يساوي إجمالي الدائن في القيود المرحّلة'
            ),
            'ledger_balanced' => $this->check(
                'ledger_balanced',
                'Ledger balanced',
                (bool) $trial['balanced'],
                $trial['balanced'] ? null : 'ميزان المراجعة غير متزن'
            ),
            'no_duplicate_depreciation' => $this->check(
                'no_duplicate_depreciation',
                'No duplicate depreciation',
                $duplicateDep === 0,
                $duplicateDep === 0 ? null : "{$duplicateDep} ترحيل إهلاك مكرر"
            ),
            'no_orphan_journal_lines' => $this->check(
                'no_orphan_journal_lines',
                'No orphan journal lines',
                $orphan === 0,
                $orphan === 0 ? null : "{$orphan} سطر قيد يتيم"
            ),
            'no_unposted_critical' => $this->check(
                'no_unposted_critical_transactions',
                'No unposted critical transactions',
                $unpostedCritical === 0,
                $unpostedCritical === 0 ? null : "{$unpostedCritical} عملية غير مرحّلة"
            ),
            'assets_reconcile' => $this->check(
                'assets_reconcile',
                'Assets reconcile',
                (bool) $assets['ok'],
                $assets['ok'] ? null : 'صافي الأصول في الأستاذ لا يطابق سجل الأصول'
            ),
            'treasury_reconciles' => $this->check(
                'treasury_reconciles',
                'Treasury reconciles',
                (bool) $treasury['ok'],
                $treasury['ok'] ? null : 'رصيد النقدية في الأستاذ لا يطابق الخزنة'
            ),
            'balance_sheet_balanced' => $this->check(
                'balance_sheet_balanced',
                'Balance Sheet balanced',
                (bool) ($sheet['equation']['balanced'] ?? false),
                ($sheet['equation']['balanced'] ?? false) ? null : ($sheet['equation']['message'] ?? '🔴 يوجد عدم اتزان محاسبي')
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function unposted(array $filters = []): array
    {
        $branchId = $this->branchId($filters);

        $drafts = JournalEntry::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('status', JournalEntry::STATUS_DRAFT)
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'entry_number', 'entry_date', 'description', 'status', 'total_debit', 'total_credit', 'is_balanced']);

        $pendingApproval = JournalEntry::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('status', JournalEntry::STATUS_PENDING_APPROVAL)
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'entry_number', 'entry_date', 'description', 'status', 'total_debit', 'total_credit', 'is_balanced']);

        $approvedUnposted = JournalEntry::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('status', JournalEntry::STATUS_APPROVED)
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'entry_number', 'entry_date', 'description', 'status', 'total_debit', 'total_credit', 'is_balanced']);

        $pendingDepreciation = Schema::connection('tenant')->hasTable('fixed_asset_depreciation_runs')
            ? FixedAssetDepreciationRun::query()
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->where('status', FixedAssetDepreciationRun::STATUS_PENDING)
                ->orderByDesc('id')
                ->limit(50)
                ->get(['id', 'period', 'status', 'total_amount', 'assets_count'])
            : collect();

        $pendingDepEntries = Schema::connection('tenant')->hasTable('fixed_asset_depreciation_entries')
            ? FixedAssetDepreciationEntry::query()
                ->when($branchId, fn ($query) => $query->whereHas('asset', fn ($asset) => $asset->where('branch_id', $branchId)))
                ->where('status', FixedAssetDepreciationEntry::STATUS_PENDING)
                ->count()
            : 0;

        $items = [
            'draft_journal_entries' => $drafts->map(fn (JournalEntry $entry) => $this->journalItem($entry))->all(),
            'pending_approval' => $pendingApproval->map(fn (JournalEntry $entry) => $this->journalItem($entry))->all(),
            'approved_unposted' => $approvedUnposted->map(fn (JournalEntry $entry) => $this->journalItem($entry))->all(),
            'pending_depreciation' => $pendingDepreciation->map(fn (FixedAssetDepreciationRun $run): array => [
                'id' => $run->id,
                'type' => 'depreciation_run',
                'label' => 'إهلاك '.$run->period,
                'status' => $run->status,
                'amount' => (float) $run->total_amount,
                'review_path' => '/accounting/assets/depreciation?period='.$run->period,
            ])->all(),
        ];

        $counts = [
            'draft_journal_entries' => $drafts->count(),
            'pending_approval' => $pendingApproval->count(),
            'approved_unposted' => $approvedUnposted->count(),
            'pending_depreciation' => $pendingDepreciation->count() + $pendingDepEntries,
        ];

        return [
            'counts' => $counts,
            'total' => array_sum($counts),
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function exceptions(array $filters = []): array
    {
        $exceptions = [];

        $unbalanced = JournalEntry::query()
            ->whereIn('status', JournalEntry::postedStatuses())
            ->where(function ($query): void {
                $query->where('is_balanced', false)
                    ->orWhereColumn('total_debit', '!=', 'total_credit');
            })
            ->limit(50)
            ->get(['id', 'entry_number', 'entry_date', 'total_debit', 'total_credit', 'difference']);

        foreach ($unbalanced as $entry) {
            $exceptions[] = $this->exception(
                'unbalanced_entry',
                'قيد مرحّل غير متوازن',
                'القيد '.$entry->entry_number.' مدين '.$entry->total_debit.' ≠ دائن '.$entry->total_credit,
                $entry->id
            );
        }

        $missingAccount = JournalEntryLine::query()
            ->where(function ($query): void {
                $query->whereNull('account_id')->orWhereDoesntHave('account');
            })
            ->with('journalEntry:id,entry_number,status')
            ->limit(50)
            ->get();

        foreach ($missingAccount as $line) {
            $exceptions[] = $this->exception(
                'account_missing',
                'حساب مفقود',
                'سطر قيد بدون حساب صالح'.($line->journalEntry?->entry_number ? ' في '.$line->journalEntry->entry_number : ''),
                $line->journal_entry_id
            );
        }

        $orphan = JournalEntryLine::query()->whereDoesntHave('journalEntry')->limit(50)->get();
        foreach ($orphan as $line) {
            $exceptions[] = $this->exception(
                'orphan_line',
                'سطر قيد يتيم',
                'سطر #'.$line->id.' غير مرتبط بقيد',
                null
            );
        }

        $closedPosted = [];
        if (Schema::connection('tenant')->hasTable('accounting_periods')) {
            $closedPeriods = AccountingPeriod::query()
                ->where(function ($query): void {
                    $query->where('is_closed', true)
                        ->orWhereIn('status', [AccountingPeriod::STATUS_CLOSED, AccountingPeriod::STATUS_LOCKED]);
                })
                ->get();
            foreach ($closedPeriods as $period) {
                $count = JournalEntry::query()
                    ->whereIn('status', [JournalEntry::STATUS_DRAFT, JournalEntry::STATUS_PENDING_APPROVAL, JournalEntry::STATUS_APPROVED])
                    ->whereDate('entry_date', '>=', $period->starts_on)
                    ->whereDate('entry_date', '<=', $period->ends_on)
                    ->count();
                if ($count > 0) {
                    $closedPosted[] = $period;
                    $exceptions[] = $this->exception(
                        'invalid_period',
                        'عمليات في فترة مغلقة',
                        $count.' عملية غير مرحّلة بتاريخ داخل '.$period->name,
                        $period->id
                    );
                }
            }
        }

        $inactivePosted = JournalEntryLine::query()
            ->whereHas('journalEntry', fn ($query) => $query->whereIn('status', JournalEntry::postedStatuses()))
            ->whereHas('account', fn ($query) => $query->where('is_active', false)->orWhere('allow_posting', false))
            ->with(['journalEntry:id,entry_number', 'account:id,code,name'])
            ->limit(20)
            ->get();
        foreach ($inactivePosted as $line) {
            $exceptions[] = $this->exception(
                'missing_mapping',
                'ترحيل على حساب غير صالح للترحيل',
                ($line->account?->code ?? '').' '.$line->account?->name.' في '.($line->journalEntry?->entry_number ?? ''),
                $line->journal_entry_id
            );
        }

        unset($closedPosted);

        $this->appendSprint5Exceptions($exceptions, $filters);

        return $exceptions;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function currentPeriod(): ?array
    {
        if (! Schema::connection('tenant')->hasTable('accounting_periods')) {
            return null;
        }

        $period = AccountingPeriod::query()
            ->whereDate('starts_on', '<=', now()->toDateString())
            ->whereDate('ends_on', '>=', now()->toDateString())
            ->first();

        if (! $period instanceof AccountingPeriod) {
            return null;
        }

        return [
            'id' => $period->id,
            'name' => $period->name,
            'status' => $period->status ?: ($period->is_closed ? AccountingPeriod::STATUS_CLOSED : AccountingPeriod::STATUS_OPEN),
            'starts_on' => $period->starts_on?->toDateString(),
            'ends_on' => $period->ends_on?->toDateString(),
        ];
    }

    private function orphanLineCount(): int
    {
        return JournalEntryLine::query()
            ->where(function ($query): void {
                $query->whereDoesntHave('journalEntry')
                    ->orWhereNull('account_id')
                    ->orWhereDoesntHave('account');
            })
            ->count();
    }

    private function duplicateDepreciationCount(): int
    {
        if (! Schema::connection('tenant')->hasTable('fixed_asset_depreciation_entries')) {
            return 0;
        }

        return (int) FixedAssetDepreciationEntry::query()
            ->selectRaw('idempotency_key, COUNT(*) as c')
            ->where('status', FixedAssetDepreciationEntry::STATUS_POSTED)
            ->whereNotNull('idempotency_key')
            ->groupBy('idempotency_key')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
    }

    /**
     * @return array{id: int, type: string, label: string, status: string, amount: float, is_balanced: bool, review_path: string}
     */
    private function journalItem(JournalEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'type' => 'journal_entry',
            'label' => $entry->entry_number.' — '.($entry->description ?: 'قيد'),
            'status' => $entry->status,
            'amount' => (float) $entry->total_debit,
            'is_balanced' => (bool) $entry->is_balanced,
            'review_path' => '/treasury/entries?id='.$entry->id,
        ];
    }

    /**
     * @return array{key: string, label: string, ok: bool, detail: string|null}
     */
    private function check(string $key, string $label, bool $ok, ?string $detail = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'ok' => $ok,
            'detail' => $detail,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $exceptions
     * @param  array<string, mixed>  $filters
     */
    private function appendSprint5Exceptions(array &$exceptions, array $filters): void
    {
        $asOf = (string) ($filters['date_to'] ?? now()->toDateString());
        $cash = $this->balances->balanceByCode('1000', $asOf, $this->branchId($filters));
        if ($cash < 0) {
            $exceptions[] = $this->exception(
                'negative_cash',
                'رصيد نقدي سالب يحتاج مراجعة',
                'رصيد الصندوق في الأستاذ '.$cash.' — لم يتم تعديل الرقم تلقائياً',
                null,
                '/accounting/ledger?account_code=1000'
            );
        }

        if (Schema::connection('tenant')->hasTable('bank_statement_lines')) {
            foreach ($this->reconciliations->unmatchedOpenItems($filters) as $item) {
                $exceptions[] = $this->exception(
                    'unmatched_bank_transactions',
                    'حركات بنك غير مطابقة',
                    $item['count'].' حركة في الكشف بدون مطابقة',
                    null,
                    $item['path']
                );
            }
        }

        if (Schema::connection('tenant')->hasTable('bank_accounts')) {
            $query = BankAccount::query()->where('status', BankAccount::STATUS_ACTIVE);
            $branchId = $this->branchId($filters);
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
            foreach ($query->get() as $bank) {
                $latest = $bank->reconciliations()->orderByDesc('id')->first();
                if (! $latest instanceof BankReconciliation || $latest->status === BankReconciliation::STATUS_DRAFT || $latest->status === BankReconciliation::STATUS_REVIEW) {
                    $exceptions[] = $this->exception(
                        'unreconciled_account',
                        'حساب بنكي غير مسوّى',
                        $bank->bank_name.' '.$bank->maskedAccountNumber(),
                        null,
                        '/accounting/reconciliation?bank_account_id='.$bank->id
                    );
                } elseif (! AccountingMoney::isZero((float) $latest->difference)) {
                    $exceptions[] = $this->exception(
                        'unreconciled_account',
                        'فرق تسوية بنكية',
                        $bank->bank_name.' فرق '.number_format((float) $latest->difference, 2),
                        null,
                        '/accounting/reconciliation/'.$latest->id
                    );
                }
            }
        }

        $ar = $this->receivables->mismatch($filters);
        if ($ar !== null) {
            $exceptions[] = $this->exception(
                'receivable_mismatch',
                'عدم تطابق ذمم العملاء مع الأستاذ',
                'الفرعي '.$ar['subledger'].' ≠ الأستاذ '.$ar['gl'],
                null,
                '/accounting/receivables'
            );
        }

        $ap = $this->payables->mismatch($filters);
        if ($ap !== null) {
            $exceptions[] = $this->exception(
                'payable_mismatch',
                'عدم تطابق ذمم الموردين مع الأستاذ',
                'الفرعي '.$ap['subledger'].' ≠ الأستاذ '.$ap['gl'],
                null,
                '/accounting/payables'
            );
        }
    }

    /**
     * @return array{code: string, title: string, detail: string, journal_entry_id: int|null, path?: string}
     */
    private function exception(string $code, string $title, string $detail, ?int $journalEntryId, ?string $path = null): array
    {
        $row = [
            'code' => $code,
            'title' => $title,
            'detail' => $detail,
            'journal_entry_id' => $journalEntryId,
        ];
        if ($path) {
            $row['path'] = $path;
        }

        return $row;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function branchId(array $filters): ?int
    {
        $branchId = $filters['branch_id'] ?? null;
        if ($branchId === null || $branchId === '') {
            return null;
        }

        return (int) $branchId;
    }
}

<?php

namespace App\Services\Tenant;

use App\Accounting\AccountingAuditService;
use App\Accounting\AccountingMoney;
use App\Accounting\AccountingPostingService;
use App\Accounting\BankMatchingEngine;
use App\Accounting\BankStatementParser;
use App\Models\Tenant\Account;
use App\Models\Tenant\BankAccount;
use App\Models\Tenant\BankReconciliation;
use App\Models\Tenant\BankReconciliationAdjustment;
use App\Models\Tenant\BankReconciliationMatch;
use App\Models\Tenant\BankStatementImport;
use App\Models\Tenant\BankStatementLine;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\JournalEntryLine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BankReconciliationService
{
    public function __construct(
        private readonly BankAccountService $banks,
        private readonly BankStatementParser $parser,
        private readonly BankMatchingEngine $matching,
        private readonly AccountingPostingService $posting,
        private readonly AccountingAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function list(array $filters = []): array
    {
        $query = BankReconciliation::query()
            ->with(['bankAccount.account:id,code,name', 'bankAccount.branch:id,name'])
            ->orderByDesc('id');
        $this->banks->applyBranchScope($query, $filters);
        if (($filters['status'] ?? '') !== '' && ($filters['status'] ?? 'all') !== 'all') {
            $query->where('status', $filters['status']);
        }
        if (($filters['bank_account_id'] ?? '') !== '') {
            $query->where('bank_account_id', (int) $filters['bank_account_id']);
        }

        return $query->limit(200)->get()->map(fn (BankReconciliation $row) => $this->presentList($row))->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function start(array $data, ?int $actorId, array $filters = []): BankReconciliation
    {
        $bank = $this->banks->findOrFail((int) $data['bank_account_id'], $filters);
        $from = (string) $data['date_from'];
        $to = (string) $data['date_to'];
        if ($from > $to) {
            throw ValidationException::withMessages(['date_from' => ['تاريخ البداية يجب أن يسبق تاريخ النهاية.']]);
        }

        $ledger = $this->banks->systemBalance($bank, $to);
        $recon = BankReconciliation::query()->create([
            'bank_account_id' => $bank->id,
            'branch_id' => $bank->branch_id,
            'date_from' => $from,
            'date_to' => $to,
            'statement_balance' => AccountingMoney::toFloat($data['statement_balance']),
            'ledger_balance' => $ledger,
            'status' => BankReconciliation::STATUS_DRAFT,
            'created_by' => $actorId,
        ]);

        $this->refreshSummary($recon);
        $this->audit->record($actorId, 'reconciliation_started', 'bank_reconciliation', $recon->id, [
            'bank_account_id' => $bank->id,
            'date_from' => $from,
            'date_to' => $to,
            'statement_balance' => $recon->statement_balance,
            'ledger_balance' => $ledger,
        ]);

        return $recon->fresh(['bankAccount.account']) ?? $recon;
    }

    public function findOrFail(int $id, array $filters = []): BankReconciliation
    {
        $query = BankReconciliation::query()->with(['bankAccount.account:id,code,name', 'bankAccount.branch:id,name']);
        $this->banks->applyBranchScope($query, $filters);

        return $query->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function previewImport(BankReconciliation $recon, UploadedFile $file): array
    {
        $this->assertEditable($recon);
        $this->validateUpload($file);
        $rows = $this->parser->parse($file);

        return [
            'filename' => $this->safeFilename($file->getClientOriginalName()),
            'row_count' => count($rows),
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<array<string, mixed>>|null  $rows
     */
    public function import(BankReconciliation $recon, ?UploadedFile $file, ?array $rows, ?int $actorId): BankStatementImport
    {
        $this->assertEditable($recon);
        $contents = null;
        $filename = 'statement.csv';
        $extension = 'csv';

        if ($file instanceof UploadedFile) {
            $this->validateUpload($file);
            $contents = file_get_contents($file->getRealPath() ?: '');
            $filename = $this->safeFilename($file->getClientOriginalName());
            $extension = strtolower((string) $file->getClientOriginalExtension()) ?: 'csv';
            $parsed = $this->parser->parse($file);
        } elseif (is_array($rows) && $rows !== []) {
            $parsed = $this->normalizeManualRows($rows);
            $contents = json_encode($parsed, JSON_UNESCAPED_UNICODE);
            $filename = 'manual-rows.json';
            $extension = 'json';
        } else {
            throw ValidationException::withMessages(['file' => ['يجب رفع ملف الكشف أو تمرير الحركات.']]);
        }

        $checksum = hash('sha256', (string) $contents);
        $duplicate = BankStatementImport::query()
            ->where('bank_account_id', $recon->bank_account_id)
            ->where('checksum', $checksum)
            ->where('status', BankStatementImport::STATUS_IMPORTED)
            ->first();
        if ($duplicate instanceof BankStatementImport) {
            throw ValidationException::withMessages(['file' => ['تم استيراد هذا الكشف مسبقاً.']]);
        }

        $storagePath = 'bank-statements/'.$recon->bank_account_id.'/'.Str::uuid().'.'.$extension;

        return DB::connection('tenant')->transaction(function () use ($recon, $parsed, $checksum, $filename, $storagePath, $contents, $actorId): BankStatementImport {
            Storage::disk('local')->put($storagePath, (string) $contents);

            $import = BankStatementImport::query()->create([
                'bank_account_id' => $recon->bank_account_id,
                'reconciliation_id' => $recon->id,
                'branch_id' => $recon->branch_id,
                'original_filename' => $filename,
                'storage_path' => $storagePath,
                'checksum' => $checksum,
                'row_count' => count($parsed),
                'status' => BankStatementImport::STATUS_IMPORTED,
                'imported_by' => $actorId,
                'imported_at' => now(),
                'metadata' => ['columns' => ['date', 'description', 'reference', 'debit', 'credit', 'amount']],
            ]);

            foreach ($parsed as $row) {
                BankStatementLine::query()->create([
                    'import_id' => $import->id,
                    'bank_account_id' => $recon->bank_account_id,
                    'line_date' => $row['date'],
                    'description' => $row['description'] ?? null,
                    'reference' => $row['reference'] ?? null,
                    'debit' => $row['debit'],
                    'credit' => $row['credit'],
                    'amount' => $row['amount'],
                    'fingerprint' => hash('sha256', $row['date'].'|'.$row['amount'].'|'.($row['reference'] ?? '').'|'.($row['description'] ?? '')),
                    'raw_payload' => $row['raw'] ?? $row,
                ]);
            }

            $this->refreshSummary($recon);
            $this->audit->record($actorId, 'bank_statement_imported', 'bank_reconciliation', $recon->id, [
                'import_id' => $import->id,
                'filename' => $filename,
                'row_count' => $import->row_count,
                'checksum' => $checksum,
            ]);

            return $import->load('lines');
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function autoMatch(BankReconciliation $recon, ?int $actorId): array
    {
        $this->assertEditable($recon);
        $created = [];
        $pairs = $this->suggestions($recon);
        $usedBank = [];
        $usedLedger = [];

        foreach ($pairs as $pair) {
            if (! $this->matching->shouldAutoMatch($pair['grade'], (int) $pair['confidence'])) {
                continue;
            }
            $bankId = (int) $pair['statement_line_id'];
            $ledgerId = (int) $pair['journal_entry_line_id'];
            if (isset($usedBank[$bankId]) || isset($usedLedger[$ledgerId])) {
                continue;
            }
            $created[] = $this->persistMatch(
                $recon,
                $bankId,
                $ledgerId,
                BankReconciliationMatch::TYPE_AUTO,
                $pair['grade'],
                (int) $pair['confidence'],
                $actorId
            );
            $usedBank[$bankId] = true;
            $usedLedger[$ledgerId] = true;
        }

        $this->refreshSummary($recon);
        $this->audit->record($actorId, 'reconciliation_auto_matched', 'bank_reconciliation', $recon->id, [
            'count' => count($created),
        ]);

        return array_map(fn (BankReconciliationMatch $match) => $this->presentMatch($match), $created);
    }

    public function manualMatch(BankReconciliation $recon, int $statementLineId, int $journalEntryLineId, ?int $actorId): BankReconciliationMatch
    {
        $this->assertEditable($recon);
        $bank = $this->statementLineFor($recon, $statementLineId);
        $ledger = $this->ledgerLineFor($recon, $journalEntryLineId);
        $score = $this->matching->score($this->bankPayload($bank), $this->ledgerPayload($ledger));
        $match = $this->persistMatch(
            $recon,
            $bank->id,
            $ledger->id,
            BankReconciliationMatch::TYPE_MANUAL,
            $score['grade'] === BankReconciliationMatch::GRADE_UNMATCHED ? BankReconciliationMatch::GRADE_POSSIBLE : $score['grade'],
            max(1, (int) $score['confidence']),
            $actorId
        );
        $this->refreshSummary($recon);
        $this->audit->record($actorId, 'reconciliation_manual_matched', 'bank_reconciliation', $recon->id, [
            'statement_line_id' => $bank->id,
            'journal_entry_id' => $ledger->journal_entry_id,
            'journal_entry_line_id' => $ledger->id,
        ]);

        return $match;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAdjustment(BankReconciliation $recon, array $data, ?int $actorId): BankReconciliationAdjustment
    {
        $this->assertEditable($recon);
        $bank = $recon->bankAccount()->with('account')->firstOrFail();
        $amount = AccountingMoney::toFloat($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => ['مبلغ التسوية يجب أن يكون أكبر من صفر.']]);
        }

        $kind = (string) ($data['kind'] ?? BankReconciliationAdjustment::KIND_BANK_FEE);
        $description = (string) ($data['description'] ?? $this->adjustmentDescription($kind));
        $contra = $this->contraAccount($kind, isset($data['expense_account_id']) ? (int) $data['expense_account_id'] : null);

        $adjustment = BankReconciliationAdjustment::query()->create([
            'reconciliation_id' => $recon->id,
            'statement_line_id' => $data['statement_line_id'] ?? null,
            'kind' => $kind,
            'amount' => $amount,
            'description' => $description,
            'expense_account_id' => $contra->id,
            'created_by' => $actorId,
        ]);

        $lines = $this->adjustmentLines($kind, $bank->account_id, $contra->id, $amount, $description);
        $entry = $this->posting->postFromSource([
            'entry_date' => $data['entry_date'] ?? $recon->date_to->toDateString(),
            'type' => JournalEntry::TYPE_ADJUSTMENT,
            'source_type' => JournalEntry::SOURCE_BANK_RECONCILIATION,
            'source_id' => $adjustment->id,
            'source_reference' => 'BR-'.$recon->id,
            'reference_number' => 'BR-'.$recon->id.'-ADJ-'.$adjustment->id,
            'description' => $description,
            'branch_id' => $recon->branch_id,
        ], $lines, $actorId);

        $adjustment->update(['journal_entry_id' => $entry->id]);
        $this->refreshSummary($recon);
        $this->audit->record($actorId, 'reconciliation_adjusted', 'bank_reconciliation', $recon->id, [
            'adjustment_id' => $adjustment->id,
            'journal_entry_id' => $entry->id,
            'kind' => $kind,
            'amount' => $amount,
        ]);

        return $adjustment->fresh(['journalEntry']) ?? $adjustment;
    }

    public function submit(BankReconciliation $recon, ?int $actorId): BankReconciliation
    {
        $this->assertEditable($recon);
        $this->refreshSummary($recon);
        $recon->update([
            'status' => BankReconciliation::STATUS_REVIEW,
            'submitted_at' => now(),
        ]);
        $this->audit->record($actorId, 'reconciliation_submitted', 'bank_reconciliation', $recon->id);

        return $recon->fresh() ?? $recon;
    }

    public function markReconciled(BankReconciliation $recon, ?int $actorId): BankReconciliation
    {
        if ($recon->isLocked()) {
            throw ValidationException::withMessages(['status' => ['التسوية مقفلة.']]);
        }
        $summary = $this->refreshSummary($recon);
        if (! AccountingMoney::isZero($summary['difference'])) {
            throw ValidationException::withMessages(['difference' => ['لا يمكن إغلاق التسوية بوجود فرق غير صفري.']]);
        }
        $recon->update([
            'status' => BankReconciliation::STATUS_RECONCILED,
            'reconciled_at' => now(),
        ]);
        $this->audit->record($actorId, 'reconciliation_reconciled', 'bank_reconciliation', $recon->id, $summary);

        return $recon->fresh() ?? $recon;
    }

    public function lock(BankReconciliation $recon, ?int $actorId): BankReconciliation
    {
        if ($recon->status !== BankReconciliation::STATUS_RECONCILED) {
            throw ValidationException::withMessages(['status' => ['يجب أن تكون التسوية متطابقة قبل القفل.']]);
        }
        $recon->update([
            'status' => BankReconciliation::STATUS_LOCKED,
            'locked_at' => now(),
            'locked_by' => $actorId,
        ]);
        $this->audit->record($actorId, 'reconciliation_locked', 'bank_reconciliation', $recon->id);

        return $recon->fresh() ?? $recon;
    }

    public function reopen(BankReconciliation $recon, string $reason, ?int $actorId): BankReconciliation
    {
        if (! in_array($recon->status, [BankReconciliation::STATUS_RECONCILED, BankReconciliation::STATUS_LOCKED], true)) {
            throw ValidationException::withMessages(['status' => ['لا يمكن إعادة فتح هذه التسوية.']]);
        }
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => ['سبب إعادة الفتح مطلوب.']]);
        }
        $recon->update([
            'status' => BankReconciliation::STATUS_DRAFT,
            'reopened_at' => now(),
            'reopened_by' => $actorId,
            'reopen_reason' => $reason,
            'locked_at' => null,
            'locked_by' => null,
            'reconciled_at' => null,
        ]);
        $this->audit->record($actorId, 'reconciliation_reopened', 'bank_reconciliation', $recon->id, [
            'reason' => $reason,
        ]);

        return $recon->fresh() ?? $recon;
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(BankReconciliation $recon): array
    {
        $summary = $this->refreshSummary($recon);
        $recon->load(['bankAccount.account:id,code,name', 'matches.statementLine', 'matches.journalEntry', 'adjustments.journalEntry']);

        return [
            'id' => $recon->id,
            'status' => $recon->status,
            'bank_account' => $this->banks->present($recon->bankAccount, $recon->date_to->toDateString()),
            'date_from' => $recon->date_from->toDateString(),
            'date_to' => $recon->date_to->toDateString(),
            'summary' => $summary,
            'statement_lines' => $this->statementWorkspace($recon),
            'ledger_lines' => $this->unmatchedLedger($recon),
            'suggestions' => $this->suggestions($recon),
            'outstanding' => $this->outstanding($recon),
            'matches' => $recon->matches->map(fn (BankReconciliationMatch $match) => $this->presentMatch($match))->all(),
            'adjustments' => $recon->adjustments->map(fn (BankReconciliationAdjustment $adj): array => [
                'id' => $adj->id,
                'kind' => $adj->kind,
                'amount' => AccountingMoney::toFloat($adj->amount),
                'description' => $adj->description,
                'journal_entry_id' => $adj->journal_entry_id,
                'source_type' => JournalEntry::SOURCE_BANK_RECONCILIATION,
                'source_id' => $adj->id,
            ])->all(),
            'audit' => $this->audit->timeline('bank_reconciliation', $recon->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function refreshSummary(BankReconciliation $recon): array
    {
        $bank = $recon->bankAccount()->with('account')->firstOrFail();
        $ledgerBalance = $this->banks->systemBalance($bank, $recon->date_to->toDateString());
        $outstanding = $this->outstanding($recon);
        $deposits = AccountingMoney::toFloat($outstanding['in_books_not_in_bank']['total_deposits'] ?? 0);
        $payments = AccountingMoney::toFloat($outstanding['in_books_not_in_bank']['total_payments'] ?? 0);
        $adjusted = AccountingMoney::toFloat(AccountingMoney::sub(
            AccountingMoney::add($recon->statement_balance, $deposits),
            $payments
        ));
        $difference = AccountingMoney::toFloat(AccountingMoney::sub($adjusted, $ledgerBalance));

        $recon->forceFill([
            'ledger_balance' => $ledgerBalance,
            'deposits_in_transit' => $deposits,
            'outstanding_payments' => $payments,
            'adjusted_bank_balance' => $adjusted,
            'difference' => $difference,
        ])->save();

        $ok = AccountingMoney::isZero($difference);

        return [
            'bank_statement_balance' => AccountingMoney::toFloat($recon->statement_balance),
            'deposits_in_transit' => $deposits,
            'outstanding_payments' => $payments,
            'adjusted_bank_balance' => $adjusted,
            'ledger_balance' => $ledgerBalance,
            'difference' => $difference,
            'reconciled' => $ok,
            'status_label' => $ok ? 'Account Reconciled' : 'Unreconciled Difference',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function controlCenter(array $filters = []): array
    {
        $asOf = (string) ($filters['date_to'] ?? now()->toDateString());
        $items = [];

        $cashQuery = \App\Models\Tenant\Cashbox::query()->where('is_active', true);
        $this->banks->applyBranchScope($cashQuery, $filters);
        foreach ($cashQuery->get() as $cashbox) {
            $boxBalance = AccountingMoney::toFloat($cashbox->current_balance);
            $gl = $cashbox->account_id
                ? $this->banks->systemBalance(new BankAccount([
                    'account_id' => $cashbox->account_id,
                    'branch_id' => $cashbox->branch_id,
                ]), $asOf)
                : $boxBalance;
            $difference = AccountingMoney::toFloat(AccountingMoney::sub($gl, $boxBalance));
            $ok = AccountingMoney::isZero($difference);
            $items[] = [
                'key' => 'cash-'.$cashbox->id,
                'type' => 'cash',
                'name' => $cashbox->name,
                'status' => $ok ? 'reconciled' : 'difference',
                'label' => $ok ? 'Cash reconciled' : 'Cash difference: '.number_format(abs($difference), 2),
                'tone' => $ok ? 'green' : 'red',
                'difference' => $difference,
                'path' => '/accounting/treasury',
            ];
        }

        $bankQuery = BankAccount::query()->where('status', BankAccount::STATUS_ACTIVE)->with('account');
        $this->banks->applyBranchScope($bankQuery, $filters);
        foreach ($bankQuery->get() as $bank) {
            $latest = $bank->reconciliations()->orderByDesc('id')->first();
            $tone = 'yellow';
            $status = 'pending';
            $label = $bank->bank_name.' pending';
            $difference = 0.0;
            if ($latest instanceof BankReconciliation) {
                $summary = [
                    'difference' => AccountingMoney::toFloat($latest->difference),
                    'status' => $latest->status,
                ];
                $difference = $summary['difference'];
                if ($latest->status === BankReconciliation::STATUS_LOCKED || ($latest->status === BankReconciliation::STATUS_RECONCILED && AccountingMoney::isZero($difference))) {
                    $tone = 'green';
                    $status = 'reconciled';
                    $label = $bank->bank_name.' reconciled';
                } elseif (! AccountingMoney::isZero($difference)) {
                    $tone = 'red';
                    $status = 'difference';
                    $label = $bank->bank_name.' difference: '.number_format(abs($difference), 2);
                }
            }
            $items[] = [
                'key' => 'bank-'.$bank->id,
                'type' => 'bank',
                'name' => $bank->bank_name,
                'masked' => $bank->maskedAccountNumber(),
                'status' => $status,
                'label' => $label,
                'tone' => $tone,
                'difference' => $difference,
                'path' => '/accounting/reconciliation?bank_account_id='.$bank->id,
            ];
        }

        return ['items' => $items];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function unmatchedOpenItems(array $filters = []): array
    {
        $query = BankReconciliation::query()
            ->whereIn('status', [BankReconciliation::STATUS_DRAFT, BankReconciliation::STATUS_REVIEW]);
        $this->banks->applyBranchScope($query, $filters);

        $items = [];
        foreach ($query->get() as $recon) {
            $unmatched = BankStatementLine::query()
                ->where('bank_account_id', $recon->bank_account_id)
                ->whereDate('line_date', '>=', $recon->date_from)
                ->whereDate('line_date', '<=', $recon->date_to)
                ->whereDoesntHave('matches', fn ($match) => $match->where('reconciliation_id', $recon->id))
                ->count();
            if ($unmatched > 0) {
                $items[] = [
                    'reconciliation_id' => $recon->id,
                    'count' => $unmatched,
                    'path' => '/accounting/reconciliation/'.$recon->id,
                ];
            }
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function suggestions(BankReconciliation $recon): array
    {
        $bankLines = $this->unmatchedStatement($recon);
        $ledgerLines = $this->unmatchedLedgerModels($recon);
        $suggestions = [];

        foreach ($bankLines as $bank) {
            $best = null;
            foreach ($ledgerLines as $ledger) {
                $score = $this->matching->score($this->bankPayload($bank), $this->ledgerPayload($ledger));
                if ($score['grade'] === BankReconciliationMatch::GRADE_UNMATCHED) {
                    continue;
                }
                if ($best === null || $score['confidence'] > $best['confidence']) {
                    $best = [
                        'statement_line_id' => $bank->id,
                        'journal_entry_line_id' => $ledger->id,
                        'journal_entry_id' => $ledger->journal_entry_id,
                        'grade' => $score['grade'],
                        'confidence' => $score['confidence'],
                        'reasons' => $score['reasons'],
                        'bank_amount' => $bank->signedAmount(),
                        'ledger_amount' => $this->ledgerSigned($ledger),
                    ];
                }
            }
            if ($best !== null) {
                $suggestions[] = $best;
            }
        }

        usort($suggestions, fn (array $a, array $b): int => $b['confidence'] <=> $a['confidence']);

        return $suggestions;
    }

    /**
     * @return array<string, mixed>
     */
    private function outstanding(BankReconciliation $recon): array
    {
        $books = $this->unmatchedLedgerModels($recon);
        $bank = $this->unmatchedStatement($recon);

        $inBooks = ['checks' => [], 'transfers' => [], 'payments' => [], 'deposits' => [], 'other' => []];
        $inBank = ['fees' => [], 'transfers' => [], 'deposits' => [], 'interest' => [], 'other' => []];
        $depositTotal = 0.0;
        $paymentTotal = 0.0;

        foreach ($books as $line) {
            $amount = $this->ledgerSigned($line);
            $bucket = $this->classifyLedger($line, $amount);
            $item = [
                'id' => $line->id,
                'journal_entry_id' => $line->journal_entry_id,
                'date' => optional($line->journalEntry?->entry_date)->toDateString(),
                'description' => $line->description ?: $line->journalEntry?->description,
                'amount' => $amount,
                'source_type' => $line->journalEntry?->source_type,
                'source_id' => $line->journalEntry?->source_id,
                'path' => '/treasury/entries?id='.$line->journal_entry_id,
            ];
            $inBooks[$bucket][] = $item;
            if ($amount > 0) {
                $depositTotal = AccountingMoney::toFloat(AccountingMoney::add($depositTotal, $amount));
            } else {
                $paymentTotal = AccountingMoney::toFloat(AccountingMoney::add($paymentTotal, abs($amount)));
            }
        }

        foreach ($bank as $line) {
            $bucket = $this->classifyBank($line);
            $inBank[$bucket][] = [
                'id' => $line->id,
                'date' => $line->line_date?->toDateString(),
                'description' => $line->description,
                'reference' => $line->reference,
                'amount' => $line->signedAmount(),
            ];
        }

        return [
            'in_books_not_in_bank' => $inBooks + [
                'total_deposits' => $depositTotal,
                'total_payments' => $paymentTotal,
            ],
            'in_bank_not_in_books' => $inBank,
        ];
    }

    private function persistMatch(
        BankReconciliation $recon,
        int $statementLineId,
        int $journalEntryLineId,
        string $type,
        string $grade,
        int $confidence,
        ?int $actorId,
    ): BankReconciliationMatch {
        $line = $this->ledgerLineFor($recon, $journalEntryLineId);
        $originalHash = $this->journalFingerprint($line->journal_entry_id);

        try {
            $match = BankReconciliationMatch::query()->create([
                'reconciliation_id' => $recon->id,
                'statement_line_id' => $statementLineId,
                'journal_entry_id' => $line->journal_entry_id,
                'journal_entry_line_id' => $line->id,
                'grade' => $grade,
                'match_type' => $type,
                'confidence' => $confidence,
                'matched_by' => $actorId,
                'matched_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException) {
            throw ValidationException::withMessages(['match' => ['هذه الحركة مربوطة مسبقاً داخل التسوية.']]);
        }

        if ($this->journalFingerprint($line->journal_entry_id) !== $originalHash) {
            throw ValidationException::withMessages(['match' => ['التسوية لا يجوز أن تعدّل القيد الأصلي.']]);
        }

        return $match;
    }

    private function journalFingerprint(int $journalEntryId): string
    {
        $entry = JournalEntry::query()->with('lines')->findOrFail($journalEntryId);
        $payload = [
            $entry->id,
            $entry->status,
            $entry->total_debit,
            $entry->total_credit,
            $entry->source_type,
            $entry->source_id,
            $entry->lines->map(fn (JournalEntryLine $line): array => [
                $line->id, $line->account_id, $line->debit, $line->credit,
            ])->all(),
        ];

        return hash('sha256', json_encode($payload) ?: '');
    }

    /**
     * @return list<BankStatementLine>
     */
    private function unmatchedStatement(BankReconciliation $recon): array
    {
        return BankStatementLine::query()
            ->where('bank_account_id', $recon->bank_account_id)
            ->whereDate('line_date', '>=', $recon->date_from)
            ->whereDate('line_date', '<=', $recon->date_to)
            ->whereDoesntHave('matches', fn ($query) => $query->where('reconciliation_id', $recon->id))
            ->orderBy('line_date')
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * @return list<JournalEntryLine>
     */
    private function unmatchedLedgerModels(BankReconciliation $recon): array
    {
        $matchedIds = $recon->matches()->whereNotNull('journal_entry_line_id')->pluck('journal_entry_line_id');

        return JournalEntryLine::query()
            ->with('journalEntry:id,entry_number,entry_date,description,reference_number,source_type,source_id,status')
            ->where('account_id', $recon->bankAccount->account_id)
            ->whereHas('journalEntry', function ($query) use ($recon): void {
                $query->whereIn('status', JournalEntry::postedStatuses())
                    ->whereDate('entry_date', '>=', $recon->date_from)
                    ->whereDate('entry_date', '<=', $recon->date_to);
            })
            ->when($matchedIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $matchedIds))
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function unmatchedLedger(BankReconciliation $recon): array
    {
        return array_map(fn (JournalEntryLine $line): array => [
            'id' => $line->id,
            'journal_entry_id' => $line->journal_entry_id,
            'journal_number' => $line->journalEntry?->entry_number,
            'date' => optional($line->journalEntry?->entry_date)->toDateString(),
            'description' => $line->description ?: $line->journalEntry?->description,
            'reference' => $line->journalEntry?->reference_number,
            'debit' => AccountingMoney::toFloat($line->debit),
            'credit' => AccountingMoney::toFloat($line->credit),
            'amount' => $this->ledgerSigned($line),
            'source_type' => $line->journalEntry?->source_type,
            'source_id' => $line->journalEntry?->source_id,
            'path' => '/treasury/entries?id='.$line->journal_entry_id,
        ], $this->unmatchedLedgerModels($recon));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function statementWorkspace(BankReconciliation $recon): array
    {
        $matched = $recon->matches()->pluck('grade', 'statement_line_id');

        return BankStatementLine::query()
            ->where('bank_account_id', $recon->bank_account_id)
            ->whereDate('line_date', '>=', $recon->date_from)
            ->whereDate('line_date', '<=', $recon->date_to)
            ->orderBy('line_date')
            ->get()
            ->map(function (BankStatementLine $line) use ($matched): array {
                $isMatched = $matched->has($line->id);

                return [
                    'id' => $line->id,
                    'date' => $line->line_date?->toDateString(),
                    'description' => $line->description,
                    'reference' => $line->reference,
                    'debit' => AccountingMoney::toFloat($line->debit),
                    'credit' => AccountingMoney::toFloat($line->credit),
                    'amount' => $line->signedAmount(),
                    'matched' => $isMatched,
                    'grade' => $isMatched ? $matched->get($line->id) : BankReconciliationMatch::GRADE_UNMATCHED,
                ];
            })
            ->all();
    }

    /**
     * @return array{id: int, date: string, amount: float, description: ?string, reference: ?string}
     */
    private function bankPayload(BankStatementLine $line): array
    {
        return [
            'id' => $line->id,
            'date' => $line->line_date?->toDateString() ?? '',
            'amount' => $line->signedAmount(),
            'description' => $line->description,
            'reference' => $line->reference,
        ];
    }

    /**
     * @return array{id: int, journal_entry_id: int, date: string, amount: float, description: ?string, reference: ?string}
     */
    private function ledgerPayload(JournalEntryLine $line): array
    {
        return [
            'id' => $line->id,
            'journal_entry_id' => $line->journal_entry_id,
            'date' => optional($line->journalEntry?->entry_date)->toDateString() ?? '',
            'amount' => $this->ledgerSigned($line),
            'description' => $line->description ?: $line->journalEntry?->description,
            'reference' => $line->journalEntry?->reference_number ?: $line->journalEntry?->source_reference,
        ];
    }

    private function ledgerSigned(JournalEntryLine $line): float
    {
        return AccountingMoney::toFloat(AccountingMoney::sub($line->debit, $line->credit));
    }

    private function statementLineFor(BankReconciliation $recon, int $id): BankStatementLine
    {
        $line = BankStatementLine::query()
            ->where('bank_account_id', $recon->bank_account_id)
            ->find($id);
        if (! $line instanceof BankStatementLine) {
            throw ValidationException::withMessages(['statement_line_id' => ['حركة الكشف غير موجودة.']]);
        }

        return $line;
    }

    private function ledgerLineFor(BankReconciliation $recon, int $id): JournalEntryLine
    {
        $line = JournalEntryLine::query()
            ->with('journalEntry')
            ->where('account_id', $recon->bankAccount->account_id)
            ->find($id);
        if (! $line instanceof JournalEntryLine || ! $line->journalEntry || ! in_array($line->journalEntry->status, JournalEntry::postedStatuses(), true)) {
            throw ValidationException::withMessages(['journal_entry_line_id' => ['سطر الأستاذ غير صالح للمطابقة.']]);
        }

        return $line;
    }

    private function classifyLedger(JournalEntryLine $line, float $amount): string
    {
        $text = mb_strtolower(($line->description ?? '').' '.($line->journalEntry?->description ?? '').' '.($line->journalEntry?->source_type ?? ''));
        if (str_contains($text, 'شيك') || str_contains($text, 'check') || str_contains($text, 'cheque')) {
            return 'checks';
        }
        if (str_contains($text, 'تحويل') || str_contains($text, 'transfer')) {
            return 'transfers';
        }
        if ($amount < 0 || str_contains($text, 'payment') || str_contains($text, 'دفعة')) {
            return 'payments';
        }
        if ($amount > 0 || str_contains($text, 'deposit') || str_contains($text, 'إيداع')) {
            return 'deposits';
        }

        return 'other';
    }

    private function classifyBank(BankStatementLine $line): string
    {
        $text = mb_strtolower(($line->description ?? '').' '.($line->reference ?? ''));
        if (str_contains($text, 'fee') || str_contains($text, 'charge') || str_contains($text, 'رسوم')) {
            return 'fees';
        }
        if (str_contains($text, 'interest') || str_contains($text, 'فائدة')) {
            return 'interest';
        }
        if (str_contains($text, 'transfer') || str_contains($text, 'تحويل')) {
            return 'transfers';
        }
        if ($line->signedAmount() > 0 || str_contains($text, 'deposit') || str_contains($text, 'إيداع')) {
            return 'deposits';
        }

        return 'other';
    }

    private function contraAccount(string $kind, ?int $accountId): Account
    {
        if ($accountId) {
            $account = Account::query()->find($accountId);
            if ($account instanceof Account) {
                return $account;
            }
        }
        $code = match ($kind) {
            BankReconciliationAdjustment::KIND_INTEREST_INCOME => '4310',
            default => '5500',
        };
        $account = Account::query()->where('code', $code)->first();
        if (! $account instanceof Account) {
            throw ValidationException::withMessages(['kind' => ['حساب التسوية البنكية غير مُعد في الدليل.']]);
        }

        return $account;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function adjustmentLines(string $kind, int $bankAccountId, int $contraId, float $amount, string $description): array
    {
        if ($kind === BankReconciliationAdjustment::KIND_INTEREST_INCOME) {
            return [
                ['account_id' => $bankAccountId, 'debit' => $amount, 'credit' => 0, 'description' => $description],
                ['account_id' => $contraId, 'debit' => 0, 'credit' => $amount, 'description' => $description],
            ];
        }

        return [
            ['account_id' => $contraId, 'debit' => $amount, 'credit' => 0, 'description' => $description],
            ['account_id' => $bankAccountId, 'debit' => 0, 'credit' => $amount, 'description' => $description],
        ];
    }

    private function adjustmentDescription(string $kind): string
    {
        return match ($kind) {
            BankReconciliationAdjustment::KIND_BANK_FEE => 'رسوم بنكية',
            BankReconciliationAdjustment::KIND_INTEREST_INCOME => 'فوائد بنكية',
            BankReconciliationAdjustment::KIND_INTEREST_EXPENSE => 'فوائد مدينة',
            default => 'تسوية بنكية',
        };
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{date: string, description: string|null, reference: string|null, debit: float, credit: float, amount: float, raw: array<string, mixed>}>
     */
    private function normalizeManualRows(array $rows): array
    {
        $parsed = [];
        foreach ($rows as $row) {
            $debit = AccountingMoney::toFloat($row['debit'] ?? 0);
            $credit = AccountingMoney::toFloat($row['credit'] ?? 0);
            $amount = isset($row['amount']) ? AccountingMoney::toFloat($row['amount']) : round($credit - $debit, 2);
            $parsed[] = [
                'date' => (string) $row['date'],
                'description' => $row['description'] ?? null,
                'reference' => $row['reference'] ?? null,
                'debit' => $debit,
                'credit' => $credit,
                'amount' => $amount,
                'raw' => $row,
            ];
        }

        return $parsed;
    }

    private function validateUpload(UploadedFile $file): void
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, ['csv', 'txt', 'xlsx'], true)) {
            throw ValidationException::withMessages(['file' => ['يُسمح فقط بملفات CSV أو XLSX.']]);
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => ['حجم الملف يتجاوز 5MB.']]);
        }
        $mime = (string) $file->getMimeType();
        $allowed = [
            'text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/octet-stream', 'application/zip',
        ];
        if ($mime !== '' && ! in_array($mime, $allowed, true) && ! str_starts_with($mime, 'text/')) {
            throw ValidationException::withMessages(['file' => ['نوع الملف غير مسموح.']]);
        }
    }

    private function safeFilename(string $name): string
    {
        $base = basename(str_replace('\\', '/', $name));
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '-', $base) ?: 'statement';

        return mb_substr($base, 0, 120);
    }

    private function assertEditable(BankReconciliation $recon): void
    {
        if (! $recon->isEditable()) {
            throw ValidationException::withMessages(['status' => ['لا يمكن تعديل تسوية مقفلة أو مغلقة.']]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function presentList(BankReconciliation $row): array
    {
        return [
            'id' => $row->id,
            'bank_name' => $row->bankAccount?->bank_name,
            'account_name' => $row->bankAccount?->name,
            'masked' => $row->bankAccount?->maskedAccountNumber(),
            'date_from' => $row->date_from?->toDateString(),
            'date_to' => $row->date_to?->toDateString(),
            'statement_balance' => AccountingMoney::toFloat($row->statement_balance),
            'ledger_balance' => AccountingMoney::toFloat($row->ledger_balance),
            'difference' => AccountingMoney::toFloat($row->difference),
            'status' => $row->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMatch(BankReconciliationMatch $match): array
    {
        return [
            'id' => $match->id,
            'statement_line_id' => $match->statement_line_id,
            'journal_entry_id' => $match->journal_entry_id,
            'journal_entry_line_id' => $match->journal_entry_line_id,
            'grade' => $match->grade,
            'match_type' => $match->match_type,
            'confidence' => $match->confidence,
        ];
    }
}

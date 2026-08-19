<?php

namespace App\Services\Tenant;

use App\Accounting\AccountingAuditService;
use App\Accounting\AccountingMoney;
use App\Models\Tenant\Account;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\Liability;
use App\Models\Tenant\LoanSettlement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LiabilityService
{
    public function __construct(
        private readonly JournalEntryService $journals,
        private readonly AccountingAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Liability::query()->with(['liabilityAccount:id,code,name', 'cashAccount:id,code,name', 'branch:id,name', 'settlements']);
        if (! empty($filters['type']) && $filters['type'] !== 'all') {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findOrFail(int $id): Liability
    {
        return Liability::query()
            ->with(['liabilityAccount', 'cashAccount', 'branch:id,name', 'settlements.journalEntry', 'receiptJournal'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $actorId): Liability
    {
        $type = (string) ($data['type'] ?? Liability::TYPE_LOAN);
        if (! in_array($type, [Liability::TYPE_LOAN, Liability::TYPE_SUPPLIER_PAYABLE, Liability::TYPE_OTHER], true)) {
            throw ValidationException::withMessages(['type' => ['نوع الالتزام غير صالح.']]);
        }
        $principal = AccountingMoney::of($data['principal'] ?? 0);
        if (AccountingMoney::cmp($principal, '0') <= 0) {
            throw ValidationException::withMessages(['principal' => ['أصل الالتزام يجب أن يكون أكبر من صفر.']]);
        }

        $liabilityAccount = $this->requireAccount((int) $data['liability_account_id']);
        $cashAccount = $this->requireAccount((int) $data['cash_account_id']);

        return DB::connection('tenant')->transaction(function () use ($type, $principal, $liabilityAccount, $cashAccount, $data, $actorId): Liability {
            $liability = Liability::query()->create([
                'type' => $type,
                'lender' => $data['lender'] ?? null,
                'number' => $data['number'] ?? null,
                'name' => trim((string) ($data['name'] ?? $data['lender'] ?? 'التزام')),
                'principal' => $principal,
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'status' => Liability::STATUS_ACTIVE,
                'branch_id' => $data['branch_id'] ?? null,
                'liability_account_id' => $liabilityAccount->id,
                'cash_account_id' => $cashAccount->id,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actorId,
            ]);

            $label = $type === Liability::TYPE_LOAN ? 'استلام قرض' : 'تسجيل التزام';
            $journal = $this->journals->createFromSource([
                'entry_date' => $liability->start_date?->toDateString(),
                'source_type' => JournalEntry::SOURCE_LOAN,
                'source_id' => $liability->id,
                'reference_number' => $liability->number ?: 'LIA-'.$liability->id,
                'description' => $label.': '.$liability->name,
                'branch_id' => $liability->branch_id,
            ], [
                ['account_id' => $cashAccount->id, 'debit' => $principal, 'credit' => 0, 'description' => $label],
                ['account_id' => $liabilityAccount->id, 'debit' => 0, 'credit' => $principal, 'description' => $label],
            ], $actorId);

            if (! $journal->is_balanced) {
                throw ValidationException::withMessages(['journal' => ['قيد الالتزام غير متوازن.']]);
            }

            $liability->forceFill([
                'receipt_journal_entry_id' => $journal->id,
                'posted_by' => $actorId,
                'posted_at' => now(),
            ])->save();

            $this->audit->record($actorId, 'posted', 'liability', $liability->id, [
                'journal_entry_id' => $journal->id,
            ]);

            return $this->findOrFail($liability->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function settle(Liability $liability, array $data, ?int $actorId): Liability
    {
        if ($liability->status === Liability::STATUS_SETTLED) {
            throw ValidationException::withMessages(['status' => ['تم سداد هذا الالتزام بالكامل.']]);
        }
        $amount = AccountingMoney::of($data['amount'] ?? 0);
        if (AccountingMoney::cmp($amount, '0') <= 0) {
            throw ValidationException::withMessages(['amount' => ['مبلغ السداد يجب أن يكون أكبر من صفر.']]);
        }
        $outstanding = $this->outstanding($liability);
        if (AccountingMoney::cmp($amount, $outstanding) > 0) {
            throw ValidationException::withMessages(['amount' => ['مبلغ السداد يتجاوز الرصيد المتبقي.']]);
        }

        $cash = $this->requireAccount((int) ($data['cash_account_id'] ?? $liability->cash_account_id));

        return DB::connection('tenant')->transaction(function () use ($liability, $amount, $cash, $data, $actorId, $outstanding): Liability {
            $settlement = LoanSettlement::query()->create([
                'liability_id' => $liability->id,
                'settled_at' => $data['settled_at'] ?? now()->toDateString(),
                'amount' => $amount,
                'cash_account_id' => $cash->id,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actorId,
            ]);

            $journal = $this->journals->createFromSource([
                'entry_date' => $settlement->settled_at?->toDateString(),
                'source_type' => JournalEntry::SOURCE_LOAN_SETTLEMENT,
                'source_id' => $settlement->id,
                'reference_number' => $settlement->reference ?: 'PAY-LIA-'.$settlement->id,
                'description' => 'سداد التزام: '.$liability->name,
                'branch_id' => $liability->branch_id,
            ], [
                ['account_id' => (int) $liability->liability_account_id, 'debit' => $amount, 'credit' => 0, 'description' => 'سداد'],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => $amount, 'description' => 'سداد'],
            ], $actorId);

            if (! $journal->is_balanced) {
                throw ValidationException::withMessages(['journal' => ['قيد السداد غير متوازن.']]);
            }

            $settlement->forceFill([
                'journal_entry_id' => $journal->id,
                'posted_by' => $actorId,
                'posted_at' => now(),
            ])->save();

            $remaining = AccountingMoney::sub($outstanding, $amount);
            if (! AccountingMoney::isPositive($remaining)) {
                $liability->forceFill(['status' => Liability::STATUS_SETTLED])->save();
            }

            $this->audit->record($actorId, 'settled', 'liability', $liability->id, [
                'settlement_id' => $settlement->id,
                'journal_entry_id' => $journal->id,
            ]);

            return $this->findOrFail($liability->id);
        });
    }

    public function outstanding(Liability $liability): string
    {
        $paid = $liability->settlements->sum(fn (LoanSettlement $row) => (float) $row->amount);

        return AccountingMoney::sub($liability->principal, $paid);
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Liability $liability): array
    {
        return [
            'id' => $liability->id,
            'type' => $liability->type,
            'lender' => $liability->lender,
            'number' => $liability->number,
            'name' => $liability->name,
            'principal' => AccountingMoney::toFloat($liability->principal),
            'outstanding' => AccountingMoney::toFloat($this->outstanding($liability)),
            'start_date' => $liability->start_date?->toDateString(),
            'due_date' => $liability->due_date?->toDateString(),
            'status' => $liability->status,
            'branch_id' => $liability->branch_id,
            'branch_name' => $liability->branch?->name,
            'notes' => $liability->notes,
            'liability_account' => $liability->liabilityAccount?->only(['id', 'code', 'name']),
            'cash_account' => $liability->cashAccount?->only(['id', 'code', 'name']),
            'receipt_journal_entry_id' => $liability->receipt_journal_entry_id,
            'receipt_journal' => $liability->receiptJournal ? [
                'id' => $liability->receiptJournal->id,
                'entry_number' => $liability->receiptJournal->entry_number,
                'status' => $liability->receiptJournal->status,
            ] : null,
            'settlements' => $liability->settlements->map(fn (LoanSettlement $row) => [
                'id' => $row->id,
                'settled_at' => $row->settled_at?->toDateString(),
                'amount' => AccountingMoney::toFloat($row->amount),
                'reference' => $row->reference,
                'journal_entry_id' => $row->journal_entry_id,
            ])->all(),
            'created_by' => $liability->created_by,
            'posted_by' => $liability->posted_by,
            'posted_at' => $liability->posted_at?->toIso8601String(),
        ];
    }

    private function requireAccount(int $id): Account
    {
        $account = Account::query()->findOrFail($id);
        if (! $account->allowsPosting()) {
            throw ValidationException::withMessages(['account_id' => ['الحساب المحدد غير قابل للترحيل.']]);
        }

        return $account;
    }
}

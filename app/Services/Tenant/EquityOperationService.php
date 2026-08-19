<?php

namespace App\Services\Tenant;

use App\Accounting\AccountingAuditService;
use App\Accounting\AccountingMoney;
use App\Models\Tenant\Account;
use App\Models\Tenant\EquityOperation;
use App\Models\Tenant\JournalEntry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EquityOperationService
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
        $query = EquityOperation::query()->with(['cashAccount:id,code,name', 'equityAccount:id,code,name', 'journalEntry']);
        if (! empty($filters['type']) && $filters['type'] !== 'all') {
            $query->where('type', $filters['type']);
        }

        return $query->orderByDesc('occurred_at')->orderByDesc('id')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $actorId): EquityOperation
    {
        $type = (string) ($data['type'] ?? '');
        if (! in_array($type, [EquityOperation::TYPE_CONTRIBUTION, EquityOperation::TYPE_DRAWING], true)) {
            throw ValidationException::withMessages(['type' => ['نوع الحركة غير صالح.']]);
        }
        $amount = AccountingMoney::of($data['amount'] ?? 0);
        if (AccountingMoney::cmp($amount, '0') <= 0) {
            throw ValidationException::withMessages(['amount' => ['المبلغ يجب أن يكون أكبر من صفر.']]);
        }

        $equityId = (int) ($data['equity_account_id'] ?? 0);
        if ($equityId <= 0) {
            $defaultCode = $type === EquityOperation::TYPE_CONTRIBUTION ? '3000' : '3100';
            $equityId = (int) (Account::query()->where('code', $defaultCode)->value('id') ?: 0);
        }
        if ($equityId <= 0) {
            throw ValidationException::withMessages(['equity_account_id' => ['حساب الملكية غير موجود.']]);
        }
        $cash = Account::query()->findOrFail((int) $data['cash_account_id']);
        $equity = Account::query()->findOrFail($equityId);
        if (! $cash->allowsPosting() || ! $equity->allowsPosting()) {
            throw ValidationException::withMessages(['account' => ['الحساب المحدد غير قابل للترحيل.']]);
        }

        return DB::connection('tenant')->transaction(function () use ($type, $amount, $cash, $equity, $data, $actorId): EquityOperation {
            $operation = EquityOperation::query()->create([
                'type' => $type,
                'owner_name' => trim((string) $data['owner_name']),
                'occurred_at' => $data['occurred_at'] ?? now()->toDateString(),
                'amount' => $amount,
                'branch_id' => $data['branch_id'] ?? null,
                'cash_account_id' => $cash->id,
                'equity_account_id' => $equity->id,
                'description' => $data['description'] ?? null,
                'attachments' => $data['attachments'] ?? null,
                'created_by' => $actorId,
            ]);

            $debitAccount = $type === EquityOperation::TYPE_CONTRIBUTION ? $cash->id : $equity->id;
            $creditAccount = $type === EquityOperation::TYPE_CONTRIBUTION ? $equity->id : $cash->id;
            $label = $type === EquityOperation::TYPE_CONTRIBUTION ? 'مساهمة مالك' : 'مسحوبات مالك';

            $journal = $this->journals->createFromSource([
                'entry_date' => $operation->occurred_at?->toDateString(),
                'source_type' => JournalEntry::SOURCE_EQUITY,
                'source_id' => $operation->id,
                'reference_number' => 'EQ-'.$operation->id,
                'description' => ($data['description'] ?? $label).' — '.$operation->owner_name,
                'branch_id' => $operation->branch_id,
            ], [
                ['account_id' => $debitAccount, 'debit' => $amount, 'credit' => 0, 'description' => $label],
                ['account_id' => $creditAccount, 'debit' => 0, 'credit' => $amount, 'description' => $label],
            ], $actorId);

            if (! $journal->is_balanced) {
                throw ValidationException::withMessages(['journal' => ['قيد حقوق الملكية غير متوازن.']]);
            }

            $operation->forceFill([
                'journal_entry_id' => $journal->id,
                'posted_by' => $actorId,
                'posted_at' => now(),
            ])->save();

            $this->audit->record($actorId, 'posted', 'equity_operation', $operation->id, [
                'type' => $type,
                'journal_entry_id' => $journal->id,
            ]);

            return $operation->fresh(['cashAccount', 'equityAccount', 'journalEntry']) ?? $operation;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function present(EquityOperation $operation): array
    {
        return [
            'id' => $operation->id,
            'type' => $operation->type,
            'owner_name' => $operation->owner_name,
            'occurred_at' => $operation->occurred_at?->toDateString(),
            'amount' => AccountingMoney::toFloat($operation->amount),
            'description' => $operation->description,
            'cash_account' => $operation->cashAccount?->only(['id', 'code', 'name']),
            'equity_account' => $operation->equityAccount?->only(['id', 'code', 'name']),
            'journal_entry_id' => $operation->journal_entry_id,
            'journal' => $operation->journalEntry ? [
                'id' => $operation->journalEntry->id,
                'entry_number' => $operation->journalEntry->entry_number,
                'status' => $operation->journalEntry->status,
            ] : null,
            'created_by' => $operation->created_by,
            'posted_by' => $operation->posted_by,
            'posted_at' => $operation->posted_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Services\Tenant;

use App\Accounting\AccountingAuditService;
use App\Accounting\AccountingMoney;
use App\Accounting\AccountingPostingService;
use App\Accounting\JournalEntryValidator;
use App\Models\Tenant\Account;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\OpeningBalanceBatch;
use App\Models\Tenant\OpeningBalanceLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpeningBalanceService
{
    public function __construct(
        private readonly JournalEntryValidator $validator,
        private readonly AccountingPostingService $posting,
        private readonly AccountingAuditService $audit,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function worksheet(): array
    {
        return Account::query()
            ->where('allow_posting', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'normal_balance'])
            ->map(fn (Account $account): array => [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'normal_balance' => $account->normal_balance,
            ])
            ->all();
    }

    public function latest(): ?OpeningBalanceBatch
    {
        return OpeningBalanceBatch::query()
            ->with(['lines.account:id,code,name,type', 'journalEntry:id,entry_number,status', 'creator:id,name', 'poster:id,name'])
            ->latest('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveDraft(array $data, ?int $actorId): OpeningBalanceBatch
    {
        $latest = $this->latest();
        if ($latest instanceof OpeningBalanceBatch && $latest->isPosted()) {
            throw ValidationException::withMessages([
                'status' => ['Opening balances are already posted. Reverse the opening journal to change them.'],
            ]);
        }

        $lines = $this->normalizedLines($data['lines'] ?? []);
        $totals = $this->validator->totals($lines);

        return DB::connection('tenant')->transaction(function () use ($data, $lines, $totals, $actorId, $latest): OpeningBalanceBatch {
            $batch = $latest instanceof OpeningBalanceBatch ? $latest : new OpeningBalanceBatch;
            $batch->fill([
                'entry_date' => $data['entry_date'] ?? now()->toDateString(),
                'branch_id' => $data['branch_id'] ?? null,
                'status' => OpeningBalanceBatch::STATUS_DRAFT,
                'description' => $data['description'] ?? 'الأرصدة الافتتاحية',
                'total_debit' => $totals['total_debit'],
                'total_credit' => $totals['total_credit'],
                'is_balanced' => $totals['is_balanced'],
                'created_by' => $batch->created_by ?? $actorId,
            ]);
            $batch->save();

            $batch->lines()->delete();
            foreach ($lines as $line) {
                OpeningBalanceLine::query()->create([
                    'opening_balance_batch_id' => $batch->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'description' => $line['description'] ?? null,
                ]);
            }

            $this->audit->record($actorId, 'updated', 'opening_balance_batch', $batch->id);

            return $this->findOrFail($batch->id);
        });
    }

    public function post(OpeningBalanceBatch $batch, ?int $actorId): OpeningBalanceBatch
    {
        if ($batch->isPosted()) {
            throw ValidationException::withMessages(['status' => ['Opening balances are already posted.']]);
        }

        $batch->loadMissing('lines');
        $lines = $batch->lines->map(fn (OpeningBalanceLine $line) => [
            'account_id' => $line->account_id,
            'debit' => $line->debit,
            'credit' => $line->credit,
            'description' => $line->description,
        ])->all();

        $this->validator->validate($lines, [
            'require_balanced' => true,
            'entry_date' => $batch->entry_date?->toDateString(),
            'branch_id' => $batch->branch_id,
        ]);

        return DB::connection('tenant')->transaction(function () use ($batch, $lines, $actorId): OpeningBalanceBatch {
            $journal = $this->posting->postFromSource([
                'entry_date' => $batch->entry_date?->toDateString() ?? now()->toDateString(),
                'type' => JournalEntry::TYPE_OPENING,
                'source_type' => JournalEntry::SOURCE_OPENING_BALANCE,
                'source_id' => $batch->id,
                'source_reference' => 'OPENING-'.$batch->id,
                'reference_number' => 'OPENING-'.$batch->id,
                'description' => $batch->description ?: 'قيد الأرصدة الافتتاحية',
                'branch_id' => $batch->branch_id,
            ], $lines, $actorId);

            $batch->update([
                'status' => OpeningBalanceBatch::STATUS_POSTED,
                'journal_entry_id' => $journal->id,
                'posted_by' => $actorId,
                'posted_at' => now(),
                'is_balanced' => true,
            ]);
            $this->audit->record($actorId, 'posted', 'opening_balance_batch', $batch->id, [
                'journal_entry_id' => $journal->id,
            ]);

            return $this->findOrFail($batch->id);
        });
    }

    public function findOrFail(int $id): OpeningBalanceBatch
    {
        return OpeningBalanceBatch::query()
            ->with(['lines.account:id,code,name,type', 'journalEntry:id,entry_number,status', 'creator:id,name', 'poster:id,name'])
            ->findOrFail($id);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function exportRows(): array
    {
        $batch = $this->latest();
        if (! $batch instanceof OpeningBalanceBatch) {
            return [];
        }

        return $batch->lines->map(fn (OpeningBalanceLine $line) => [
            $line->account?->code,
            $line->account?->name,
            (float) $line->debit,
            (float) $line->credit,
            $line->description,
        ])->all();
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    private function normalizedLines(array $lines): array
    {
        $normalized = [];
        foreach ($lines as $line) {
            $debit = AccountingMoney::toFloat($line['debit'] ?? 0);
            $credit = AccountingMoney::toFloat($line['credit'] ?? 0);
            if ($debit == 0.0 && $credit == 0.0) {
                continue;
            }
            $normalized[] = [
                'account_id' => (int) $line['account_id'],
                'debit' => $debit,
                'credit' => $credit,
                'description' => $line['description'] ?? null,
            ];
        }

        if (count($normalized) < 2) {
            throw ValidationException::withMessages(['lines' => ['At least two opening balance lines are required.']]);
        }

        return $normalized;
    }
}

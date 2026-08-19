<?php

namespace App\Accounting\Actions;

use App\Accounting\AccountingAuditService;
use App\Accounting\AccountingPostingService;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReverseJournalEntryAction
{
    public function __construct(
        private readonly AccountingPostingService $posting,
        private readonly AccountingAuditService $audit,
    ) {}

    public function execute(JournalEntry $entry, ?int $actorId, ?string $reason = null): JournalEntry
    {
        if (! $entry->isPosted()) {
            throw ValidationException::withMessages(['status' => ['Only posted entries can be reversed.']]);
        }

        if ($entry->isReversed()) {
            throw ValidationException::withMessages(['status' => ['Journal entry is already reversed.']]);
        }

        $entry->loadMissing('lines');

        return DB::connection('tenant')->transaction(function () use ($entry, $actorId, $reason): JournalEntry {
            $reversalLines = $entry->lines->map(fn (JournalEntryLine $line) => [
                'account_id' => $line->account_id,
                'debit' => $line->credit,
                'credit' => $line->debit,
                'description' => $line->description ? 'Reversal: '.$line->description : 'Reversal line',
                'branch_id' => $line->branch_id,
                'cost_center_id' => $line->cost_center_id,
            ])->all();

            $reversal = $this->posting->persistPosted([
                'entry_date' => now()->toDateString(),
                'type' => JournalEntry::TYPE_REVERSAL,
                'source_type' => JournalEntry::SOURCE_REVERSAL,
                'source_id' => $entry->id,
                'reference_number' => $entry->entry_number,
                'source_reference' => $entry->entry_number,
                'description' => 'Reversal of '.$entry->entry_number,
                'branch_id' => $entry->branch_id,
                'reversed_entry_id' => $entry->id,
                'metadata' => ['reversal_of_id' => $entry->id],
            ], $reversalLines, $actorId, persistEvent: true);

            $entry->update([
                'status' => JournalEntry::STATUS_REVERSED,
                'reversed_by' => $actorId,
                'reversed_at' => now(),
                'reversal_reason' => $reason ?: 'Reversal of posted journal '.$entry->entry_number,
            ]);

            $this->audit->record($actorId, 'reversed', 'journal_entry', $entry->id, [
                'reversal_id' => $reversal->id,
                'reversal_number' => $reversal->entry_number,
            ]);

            return $reversal;
        });
    }
}

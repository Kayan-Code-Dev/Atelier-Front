<?php

namespace App\Accounting;

use App\Models\Tenant\Account;
use App\Models\Tenant\AccountingEvent;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\JournalEntryLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AccountingPostingService
{
    public function __construct(
        private readonly JournalEntryValidator $validator,
        private readonly JournalNumberGenerator $numbers,
        private readonly AccountingAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $header
     * @param  list<array<string, mixed>>  $lines
     */
    public function postFromSource(array $header, array $lines, ?int $actorId): JournalEntry
    {
        $sourceType = (string) ($header['source_type'] ?? JournalEntry::SOURCE_SYSTEM);
        $sourceId = isset($header['source_id']) ? (int) $header['source_id'] : null;

        if ($sourceId !== null) {
            $existing = JournalEntry::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->whereNotIn('status', [JournalEntry::STATUS_CANCELLED, JournalEntry::STATUS_REVERSED])
                ->first();
            if ($existing instanceof JournalEntry) {
                return $existing->load(['lines.account', 'branch', 'creator', 'approver', 'poster']);
            }
        }

        return $this->persistPosted($header, $lines, $actorId, persistEvent: true);
    }

    public function postExisting(JournalEntry $entry, ?int $actorId): JournalEntry
    {
        if ($entry->isCancelled()) {
            throw ValidationException::withMessages(['status' => ['Cannot post a cancelled journal entry.']]);
        }

        if ($entry->isPosted()) {
            throw ValidationException::withMessages(['status' => ['Journal entry is already posted.']]);
        }

        if ($entry->isReversed()) {
            throw ValidationException::withMessages(['status' => ['Cannot post a reversed journal entry.']]);
        }

        $entry->loadMissing('lines');
        $lines = $entry->lines->map(fn (JournalEntryLine $line) => [
            'account_id' => $line->account_id,
            'debit' => $line->debit,
            'credit' => $line->credit,
            'description' => $line->description,
            'branch_id' => $line->branch_id,
            'cost_center_id' => $line->cost_center_id,
        ])->all();

        $this->validator->validate($lines, [
            'require_balanced' => true,
            'entry_date' => $entry->entry_date?->toDateString(),
            'branch_id' => $entry->branch_id,
        ]);

        $totals = $this->validator->totals($lines);
        $now = now();

        return DB::connection('tenant')->transaction(function () use ($entry, $actorId, $totals, $now): JournalEntry {
            $entry->update([
                'status' => JournalEntry::STATUS_POSTED,
                'total_debit' => $totals['total_debit'],
                'total_credit' => $totals['total_credit'],
                'difference' => $totals['difference'],
                'is_balanced' => true,
                'approved_by' => $actorId,
                'approved_at' => $now,
                'posted_by' => $actorId,
                'posted_at' => $now,
            ]);

            $this->audit->record($actorId, 'posted', 'journal_entry', $entry->id, [
                'entry_number' => $entry->entry_number,
            ]);

            return $entry->fresh([
                'lines.account',
                'branch',
                'creator',
                'approver',
                'poster',
            ]) ?? $entry;
        });
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  list<array<string, mixed>>  $lines
     */
    public function persistPosted(array $header, array $lines, ?int $actorId, bool $persistEvent = false): JournalEntry
    {
        $this->validator->validate($lines, [
            'require_balanced' => true,
            'entry_date' => $header['entry_date'] ?? now()->toDateString(),
            'branch_id' => $header['branch_id'] ?? null,
        ]);

        $totals = $this->validator->totals($lines);

        return DB::connection('tenant')->transaction(function () use ($header, $lines, $totals, $actorId, $persistEvent): JournalEntry {
            $date = Carbon::parse($header['entry_date'] ?? now());
            $now = now();
            $sourceReference = $header['source_reference'] ?? $header['reference_number'] ?? null;

            $entry = JournalEntry::query()->create([
                'entry_number' => $this->numbers->next($date),
                'entry_date' => $date->toDateString(),
                'type' => $header['type'] ?? JournalEntry::TYPE_NORMAL,
                'source_type' => $header['source_type'] ?? JournalEntry::SOURCE_SYSTEM,
                'source_id' => $header['source_id'] ?? null,
                'reference_number' => $header['reference_number'] ?? null,
                'source_reference' => $sourceReference,
                'description' => $header['description'] ?? null,
                'notes' => $header['notes'] ?? null,
                'status' => JournalEntry::STATUS_POSTED,
                'total_debit' => $totals['total_debit'],
                'total_credit' => $totals['total_credit'],
                'difference' => $totals['difference'],
                'is_balanced' => true,
                'branch_id' => $header['branch_id'] ?? null,
                'created_by' => $actorId,
                'approved_by' => $actorId,
                'approved_at' => $now,
                'posted_by' => $actorId,
                'posted_at' => $now,
                'reversed_entry_id' => $header['reversed_entry_id'] ?? null,
                'metadata' => $header['metadata'] ?? null,
            ]);

            $this->syncLines($entry, $lines);

            if ($persistEvent && Schema::connection('tenant')->hasTable('accounting_events')) {
                AccountingEvent::query()->create([
                    'event_type' => (string) ($header['source_type'] ?? JournalEntry::SOURCE_SYSTEM),
                    'branch_id' => $header['branch_id'] ?? null,
                    'source_type' => $header['source_type'] ?? null,
                    'source_id' => $header['source_id'] ?? null,
                    'occurred_at' => $date,
                    'payload' => [
                        'header' => $header,
                        'line_count' => count($lines),
                    ],
                    'journal_entry_id' => $entry->id,
                ]);
            }

            $this->audit->record($actorId, 'posted', 'journal_entry', $entry->id, [
                'entry_number' => $entry->entry_number,
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
            ]);

            return $entry->fresh([
                'lines.account',
                'branch',
                'creator',
                'approver',
                'poster',
            ]) ?? $entry;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function syncLines(JournalEntry $entry, array $lines): void
    {
        $entry->lines()->delete();

        foreach ($lines as $line) {
            /** @var Account $account */
            $account = Account::query()->findOrFail((int) $line['account_id']);

            JournalEntryLine::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $account->id,
                'account_code' => $account->code,
                'account_name' => $account->name,
                'debit' => AccountingMoney::of($line['debit'] ?? 0),
                'credit' => AccountingMoney::of($line['credit'] ?? 0),
                'description' => $line['description'] ?? null,
                'branch_id' => $line['branch_id'] ?? $entry->branch_id,
                'cost_center_id' => $line['cost_center_id'] ?? null,
            ]);
        }
    }
}

<?php

namespace App\Services\Tenant;

use App\Accounting\AccountingAuditService;
use App\Accounting\AccountingPostingService;
use App\Accounting\Actions\ReverseJournalEntryAction;
use App\Accounting\JournalEntryValidator;
use App\Accounting\JournalNumberGenerator;
use App\Models\Tenant\JournalEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalEntryService
{
    public function __construct(
        private readonly JournalEntryValidator $validator,
        private readonly AccountingPostingService $posting,
        private readonly ReverseJournalEntryAction $reverseAction,
        private readonly JournalNumberGenerator $numbers,
        private readonly AccountingAuditService $audit,
    ) {}
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->with([
                'branch:id,name',
                'creator:id,name',
                'lines' => fn ($query) => $query->orderBy('id'),
            ])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): JournalEntry
    {
        return JournalEntry::query()
            ->with([
                'lines.account:id,code,name',
                'lines.branch:id,name',
                'branch:id,name',
                'creator:id,name',
                'submitter:id,name',
                'approver:id,name',
                'poster:id,name',
                'canceller:id,name',
                'reverser:id,name',
                'reversedEntry:id,entry_number',
                'reversals:id,entry_number,status',
            ])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int|float|bool>
     */
    public function summary(array $filters = []): array
    {
        $query = $this->filteredQuery($filters);

        $rows = (clone $query)->get(['total_debit', 'total_credit', 'difference', 'status', 'is_balanced']);

        return [
            'total_debit' => round((float) $rows->sum('total_debit'), 2),
            'total_credit' => round((float) $rows->sum('total_credit'), 2),
            'difference' => round((float) $rows->sum('total_debit') - (float) $rows->sum('total_credit'), 2),
            'approved_count' => $rows->whereIn('status', JournalEntry::postedStatuses())->count(),
            'posted_count' => $rows->whereIn('status', JournalEntry::postedStatuses())->count(),
            'reviewed_count' => $rows->where('status', JournalEntry::STATUS_PENDING_APPROVAL)->count(),
            'accepted_count' => $rows->where('status', JournalEntry::STATUS_APPROVED)->count(),
            'draft_count' => $rows->where('status', JournalEntry::STATUS_DRAFT)->count(),
            'cancelled_count' => $rows->where('status', JournalEntry::STATUS_CANCELLED)->count(),
            'reversed_count' => $rows->where('status', JournalEntry::STATUS_REVERSED)->count(),
            'unbalanced_count' => $rows->where('is_balanced', false)->count(),
            'entries_count' => $rows->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $actorId): JournalEntry
    {
        $lines = $data['lines'] ?? [];
        $this->validator->validate($lines, [
            'require_balanced' => false,
            'entry_date' => $data['entry_date'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
        ]);

        return DB::connection('tenant')->transaction(function () use ($data, $lines, $actorId): JournalEntry {
            $totals = $this->computeTotals($lines);

            $entry = JournalEntry::query()->create([
                'entry_number' => $this->numbers->next(Carbon::parse($data['entry_date'])),
                'entry_date' => $data['entry_date'],
                'type' => $data['type'] ?? JournalEntry::TYPE_NORMAL,
                'source_type' => $data['source_type'] ?? JournalEntry::SOURCE_MANUAL,
                'source_id' => $data['source_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'source_reference' => $data['source_reference'] ?? $data['reference_number'] ?? null,
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'attachments' => $data['attachments'] ?? null,
                'status' => JournalEntry::STATUS_DRAFT,
                'total_debit' => $totals['total_debit'],
                'total_credit' => $totals['total_credit'],
                'difference' => $totals['difference'],
                'is_balanced' => $totals['is_balanced'],
                'branch_id' => $data['branch_id'] ?? null,
                'created_by' => $actorId,
            ]);

            $this->syncLines($entry, $lines);
            $this->audit->record($actorId, 'created', 'journal_entry', $entry->id, [
                'entry_number' => $entry->entry_number,
            ]);

            return $this->findOrFail($entry->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(JournalEntry $entry, array $data, ?int $actorId): JournalEntry
    {
        $this->assertEditable($entry);

        $lines = $data['lines'] ?? null;
        if ($lines !== null) {
            $this->validator->validate($lines, [
                'require_balanced' => false,
                'entry_date' => $data['entry_date'] ?? $entry->entry_date?->toDateString(),
                'branch_id' => array_key_exists('branch_id', $data) ? $data['branch_id'] : $entry->branch_id,
            ]);
        }

        return DB::connection('tenant')->transaction(function () use ($entry, $data, $lines, $actorId): JournalEntry {
            $payload = array_filter([
                'entry_date' => $data['entry_date'] ?? null,
                'type' => $data['type'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'description' => $data['description'] ?? null,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : null,
                'attachments' => array_key_exists('attachments', $data) ? $data['attachments'] : null,
                'branch_id' => array_key_exists('branch_id', $data) ? $data['branch_id'] : null,
            ], fn ($value) => $value !== null);

            if ($lines !== null) {
                $totals = $this->computeTotals($lines);
                $payload = array_merge($payload, $totals);
                $this->syncLines($entry, $lines);
            }

            if ($payload !== []) {
                $entry->update($payload);
            }

            $this->audit->record($actorId, 'updated', 'journal_entry', $entry->id);

            return $this->findOrFail($entry->id);
        });
    }

    public function approve(JournalEntry $entry, ?int $actorId): JournalEntry
    {
        return $this->post($entry, $actorId);
    }

    public function submit(JournalEntry $entry, ?int $actorId): JournalEntry
    {
        if (! $entry->isDraft()) {
            throw ValidationException::withMessages(['status' => ['Only draft entries can be submitted for review.']]);
        }

        $this->assertBalanced($entry);

        $entry->update([
            'status' => JournalEntry::STATUS_PENDING_APPROVAL,
            'submitted_by' => $actorId,
            'submitted_at' => now(),
        ]);
        $this->audit->record($actorId, 'submitted', 'journal_entry', $entry->id, [
            'entry_number' => $entry->entry_number,
        ]);

        return $this->findOrFail($entry->id);
    }

    public function accept(JournalEntry $entry, ?int $actorId): JournalEntry
    {
        if (! in_array($entry->status, [JournalEntry::STATUS_DRAFT, JournalEntry::STATUS_PENDING_APPROVAL], true)) {
            throw ValidationException::withMessages(['status' => ['Only draft or reviewed entries can be approved.']]);
        }

        $this->assertBalanced($entry);

        $entry->update([
            'status' => JournalEntry::STATUS_APPROVED,
            'approved_by' => $actorId,
            'approved_at' => now(),
        ]);
        $this->audit->record($actorId, 'approved', 'journal_entry', $entry->id, [
            'entry_number' => $entry->entry_number,
        ]);

        return $this->findOrFail($entry->id);
    }

    public function post(JournalEntry $entry, ?int $actorId): JournalEntry
    {
        if ($entry->isPosted()) {
            throw ValidationException::withMessages(['status' => ['Journal entry is already posted.']]);
        }

        return $this->posting->postExisting($entry, $actorId);
    }

    public function deleteDraft(JournalEntry $entry, ?int $actorId): void
    {
        if (! $entry->canDelete()) {
            throw ValidationException::withMessages(['status' => ['Only draft entries can be deleted.']]);
        }

        $id = $entry->id;
        $number = $entry->entry_number;
        $entry->lines()->delete();
        $entry->delete();
        $this->audit->record($actorId, 'deleted', 'journal_entry', $id, [
            'entry_number' => $number,
        ]);
    }

    public function cancel(JournalEntry $entry, ?string $reason, ?int $actorId): JournalEntry
    {
        if ($entry->isCancelled()) {
            throw ValidationException::withMessages(['status' => ['Journal entry is already cancelled.']]);
        }

        if ($entry->isImmutable()) {
            throw ValidationException::withMessages(['status' => ['Posted entries cannot be cancelled. Use reversal.']]);
        }

        if (! in_array($entry->status, [JournalEntry::STATUS_DRAFT, JournalEntry::STATUS_PENDING_APPROVAL], true)) {
            throw ValidationException::withMessages(['status' => ['Only draft or reviewed entries can be cancelled.']]);
        }

        $entry->update([
            'status' => JournalEntry::STATUS_CANCELLED,
            'cancelled_by' => $actorId,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        $this->audit->record($actorId, 'cancelled', 'journal_entry', $entry->id, [
            'reason' => $reason,
        ]);

        return $this->findOrFail($entry->id);
    }

    public function reverse(JournalEntry $entry, ?int $actorId, ?string $reason = null): JournalEntry
    {
        $reason = trim((string) $reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reversal_reason' => ['Reversal reason is required.']]);
        }

        return $this->reverseAction->execute($entry, $actorId, $reason);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function exportRows(array $filters): array
    {
        return $this->filteredQuery($filters)
            ->with(['branch:id,name', 'creator:id,name'])
            ->orderByDesc('entry_date')
            ->get()
            ->map(fn (JournalEntry $entry) => [
                'entry_number' => $entry->entry_number,
                'entry_date' => $entry->entry_date?->toDateString(),
                'type' => $entry->type,
                'source_type' => $entry->source_type,
                'reference_number' => $entry->reference_number,
                'description' => $entry->description,
                'total_debit' => (float) $entry->total_debit,
                'total_credit' => (float) $entry->total_credit,
                'difference' => (float) $entry->difference,
                'status' => $entry->status,
                'branch' => $entry->branch?->name,
                'created_by' => $entry->creator?->name,
            ])
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function createFromSource(array $header, array $lines, ?int $actorId): JournalEntry
    {
        return $this->posting->postFromSource($header, $lines, $actorId);
    }

    public function findBySource(string $sourceType, int $sourceId): ?JournalEntry
    {
        return JournalEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->whereNotIn('status', [JournalEntry::STATUS_CANCELLED, JournalEntry::STATUS_REVERSED])
            ->first();
    }

    public function cancelBySource(string $sourceType, int $sourceId, ?int $actorId): ?JournalEntry
    {
        $entry = $this->findBySource($sourceType, $sourceId);
        if (! $entry instanceof JournalEntry) {
            return null;
        }

        if ($entry->isCancelled() || $entry->isReversed()) {
            return $entry;
        }

        if ($entry->isPosted()) {
            $this->reverse($entry, $actorId, 'Source document was reversed or cancelled.');

            return $this->findOrFail($entry->id);
        }

        return $this->cancel($entry, 'Cancelled because source document was reversed or cancelled.', $actorId);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = JournalEntry::query();

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('entry_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('lines', function (Builder $lineQuery) use ($search): void {
                        $lineQuery->where('account_name', 'like', "%{$search}%")
                            ->orWhere('account_code', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('entry_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('entry_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            if (in_array($filters['status'], ['approved', 'posted'], true)) {
                $query->whereIn('status', JournalEntry::postedStatuses());
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (! empty($filters['created_by'])) {
            $query->where('created_by', (int) $filters['created_by']);
        }

        if (! empty($filters['type']) && $filters['type'] !== 'all') {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['source_type']) && $filters['source_type'] !== 'all') {
            $query->where('source_type', $filters['source_type']);
        }

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }
        app(\App\Support\Tenant\AuthorizedBranchAccess::class)->constrain($query);

        if (! empty($filters['account_id'])) {
            $accountId = (int) $filters['account_id'];
            $query->whereHas('lines', fn (Builder $lineQuery) => $lineQuery->where('account_id', $accountId));
        }

        if (isset($filters['is_balanced']) && $filters['is_balanced'] !== '' && $filters['is_balanced'] !== 'all') {
            $query->where('is_balanced', filter_var($filters['is_balanced'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{total_debit: string, total_credit: string, difference: string, is_balanced: bool}
     */
    private function computeTotals(array $lines): array
    {
        return $this->validator->totals($lines);
    }

    private function assertEditable(JournalEntry $entry): void
    {
        if ($entry->isCancelled() || $entry->isReversed()) {
            throw ValidationException::withMessages(['status' => ['This journal entry cannot be edited.']]);
        }

        if ($entry->isImmutable()) {
            throw ValidationException::withMessages(['status' => ['Posted or approved entries cannot be edited. Use reversal.']]);
        }
    }

    private function assertBalanced(JournalEntry $entry): void
    {
        $entry->loadMissing('lines');
        $this->validator->validate($entry->lines->map(fn ($line) => [
            'account_id' => $line->account_id,
            'debit' => $line->debit,
            'credit' => $line->credit,
            'description' => $line->description,
            'branch_id' => $line->branch_id,
            'cost_center_id' => $line->cost_center_id,
        ])->all(), [
            'require_balanced' => true,
            'entry_date' => $entry->entry_date?->toDateString(),
            'branch_id' => $entry->branch_id,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function syncLines(JournalEntry $entry, array $lines): void
    {
        $this->posting->syncLines($entry, $lines);
        $entry->refresh();
    }
}

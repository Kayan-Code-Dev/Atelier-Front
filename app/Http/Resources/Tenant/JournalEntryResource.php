<?php

namespace App\Http\Resources\Tenant;

use App\Accounting\JournalSourcePresenter;
use App\Models\Tenant\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin JournalEntry */
class JournalEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $source = JournalSourcePresenter::present($this->resource);

        return [
            'id' => $this->id,
            'entry_number' => $this->entry_number,
            'journal_number' => $this->entry_number,
            'entry_date' => $this->entry_date?->toDateString(),
            'date' => $this->entry_date?->toDateString(),
            'description' => $this->description,
            'type' => $this->type,
            'entry_type' => $this->type,
            'source_type' => $source['source_type'],
            'source_id' => $source['source_id'],
            'source_reference' => $source['source_reference'],
            'source_label' => $source['source_label'],
            'source_url' => $source['source_url'],
            'reference_number' => $this->reference_number,
            'total_debit' => (float) $this->total_debit,
            'total_credit' => (float) $this->total_credit,
            'difference' => (float) $this->difference,
            'is_balanced' => (bool) $this->is_balanced,
            'status' => $this->status,
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch?->id,
                'name' => $this->branch?->name,
            ]),
            'branch_name' => $this->branch?->name,
            'created_by' => $this->creator?->name,
            'created_by_id' => $this->created_by,
            'approved_by' => $this->approver?->name,
            'approved_by_id' => $this->approved_by,
            'cancelled_by' => $this->canceller?->name,
            'cancelled_by_id' => $this->cancelled_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'posted_by' => $this->poster?->name,
            'posted_by_id' => $this->posted_by,
            'posted_at' => $this->posted_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'reversed_by' => $this->reverser?->name,
            'reversed_by_id' => $this->reversed_by,
            'reversed_at' => $this->reversed_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'reversal_reason' => $this->reversal_reason,
            'needs_review' => (bool) $this->needs_review,
            'reversed_entry_id' => $this->reversed_entry_id,
            'reversal_of_id' => $this->reversed_entry_id,
            'reversed_entry' => $this->whenLoaded('reversedEntry', fn () => [
                'id' => $this->reversedEntry?->id,
                'entry_number' => $this->reversedEntry?->entry_number,
            ]),
            'lines_count' => $this->relationLoaded('lines') ? $this->lines->count() : null,
            'lines' => JournalEntryLineResource::collection($this->whenLoaded('lines')),
            'notes' => $this->notes,
            'attachments' => $this->attachments ?? [],
            'submitted_by' => $this->submitter?->name,
            'submitted_by_id' => $this->submitted_by,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
        ];
    }
}

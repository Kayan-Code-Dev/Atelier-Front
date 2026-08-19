<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationMatch extends BaseTenantModel
{
    public const GRADE_EXACT = 'exact';

    public const GRADE_LIKELY = 'likely';

    public const GRADE_POSSIBLE = 'possible';

    public const GRADE_UNMATCHED = 'unmatched';

    public const TYPE_AUTO = 'auto';

    public const TYPE_MANUAL = 'manual';

    protected $fillable = [
        'reconciliation_id',
        'statement_line_id',
        'journal_entry_id',
        'journal_entry_line_id',
        'grade',
        'match_type',
        'confidence',
        'matched_by',
        'matched_at',
    ];

    protected function casts(): array
    {
        return [
            'matched_at' => 'datetime',
        ];
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function statementLine(): BelongsTo
    {
        return $this->belongsTo(BankStatementLine::class, 'statement_line_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function journalEntryLine(): BelongsTo
    {
        return $this->belongsTo(JournalEntryLine::class, 'journal_entry_line_id');
    }
}

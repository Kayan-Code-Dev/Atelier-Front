<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationAdjustment extends BaseTenantModel
{
    public const KIND_BANK_FEE = 'bank_fee';

    public const KIND_INTEREST_INCOME = 'interest_income';

    public const KIND_INTEREST_EXPENSE = 'interest_expense';

    public const KIND_OTHER = 'other';

    protected $fillable = [
        'reconciliation_id',
        'statement_line_id',
        'journal_entry_id',
        'kind',
        'amount',
        'description',
        'expense_account_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
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
}

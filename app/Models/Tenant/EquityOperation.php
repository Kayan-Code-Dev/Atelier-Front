<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquityOperation extends BaseTenantModel
{
    public const TYPE_CONTRIBUTION = 'contribution';

    public const TYPE_DRAWING = 'drawing';

    protected $fillable = [
        'type',
        'owner_name',
        'occurred_at',
        'amount',
        'branch_id',
        'cash_account_id',
        'equity_account_id',
        'description',
        'attachments',
        'journal_entry_id',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'occurred_at' => 'date',
        'amount' => 'decimal:2',
        'attachments' => 'array',
        'posted_at' => 'datetime',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cash_account_id');
    }

    public function equityAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'equity_account_id');
    }
}

<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanSettlement extends BaseTenantModel
{
    protected $fillable = [
        'liability_id',
        'settled_at',
        'amount',
        'cash_account_id',
        'reference',
        'notes',
        'journal_entry_id',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'settled_at' => 'date',
        'amount' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function liability(): BelongsTo
    {
        return $this->belongsTo(Liability::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}

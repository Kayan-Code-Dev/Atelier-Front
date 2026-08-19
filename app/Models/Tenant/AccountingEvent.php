<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingEvent extends BaseTenantModel
{
    protected $fillable = [
        'event_type',
        'branch_id',
        'source_type',
        'source_id',
        'occurred_at',
        'payload',
        'journal_entry_id',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'payload' => 'array',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

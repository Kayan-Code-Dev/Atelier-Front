<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAssetDepreciationRun extends BaseTenantModel
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'period',
        'branch_id',
        'status',
        'assets_count',
        'total_amount',
        'journal_entry_id',
        'idempotency_key',
        'created_by',
        'posted_by',
        'reversed_by',
        'posted_at',
        'reversed_at',
        'reversal_reason',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(FixedAssetDepreciationEntry::class, 'run_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

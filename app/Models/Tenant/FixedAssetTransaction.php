<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAssetTransaction extends BaseTenantModel
{
    protected $fillable = [
        'fixed_asset_id',
        'type',
        'occurred_at',
        'amount',
        'journal_entry_id',
        'payload',
        'created_by',
    ];

    protected $casts = [
        'occurred_at' => 'date',
        'amount' => 'decimal:2',
        'payload' => 'array',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}

<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAssetDisposal extends BaseTenantModel
{
    public const TYPE_SALE = 'sale';

    public const TYPE_RETIREMENT = 'retirement';

    public const TYPE_LOSS = 'loss';

    public const TYPE_DAMAGE = 'damage';

    protected $fillable = [
        'fixed_asset_id',
        'type',
        'disposed_at',
        'acquisition_cost',
        'accumulated_depreciation',
        'net_book_value',
        'proceeds',
        'gain_loss',
        'proceeds_account_id',
        'journal_entry_id',
        'notes',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'disposed_at' => 'date',
        'acquisition_cost' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'net_book_value' => 'decimal:2',
        'proceeds' => 'decimal:2',
        'gain_loss' => 'decimal:2',
        'posted_at' => 'datetime',
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

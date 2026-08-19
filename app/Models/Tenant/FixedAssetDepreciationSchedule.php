<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAssetDepreciationSchedule extends BaseTenantModel
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'fixed_asset_id',
        'period',
        'sequence',
        'amount',
        'accumulated',
        'book_value',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'accumulated' => 'decimal:2',
        'book_value' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }
}

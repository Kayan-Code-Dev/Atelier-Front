<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAssetDepreciationEntry extends BaseTenantModel
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'run_id',
        'fixed_asset_id',
        'schedule_id',
        'period',
        'amount',
        'status',
        'journal_entry_id',
        'idempotency_key',
        'posted_by',
        'reversed_by',
        'posted_at',
        'reversed_at',
        'reversal_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(FixedAssetDepreciationRun::class, 'run_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(FixedAssetDepreciationSchedule::class, 'schedule_id');
    }
}

<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAssetTransfer extends BaseTenantModel
{
    protected $fillable = [
        'fixed_asset_id',
        'transferred_at',
        'from_branch_id',
        'to_branch_id',
        'from_location',
        'to_location',
        'reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transferred_at' => 'date',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }
}

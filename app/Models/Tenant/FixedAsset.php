<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends BaseTenantModel
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_FULLY_DEPRECIATED = 'fully_depreciated';

    public const STATUS_DISPOSED = 'disposed';

    public const STATUS_RETIRED = 'retired';

    public const METHOD_STRAIGHT_LINE = 'straight_line';

    public const ACQUIRE_CASH = 'cash';

    public const ACQUIRE_PAYABLE = 'payable';

    protected $fillable = [
        'branch_id',
        'category_id',
        'asset_code',
        'name',
        'description',
        'location',
        'purchase_date',
        'placed_in_service_date',
        'acquisition_cost',
        'salvage_value',
        'useful_life',
        'useful_life_unit',
        'depreciation_method',
        'acquisition_method',
        'status',
        'asset_account_id',
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'disposal_gain_loss_account_id',
        'payment_account_id',
        'purchase_journal_entry_id',
        'created_by',
        'updated_by',
        'attachments',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'placed_in_service_date' => 'date',
        'acquisition_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'attachments' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FixedAssetCategory::class, 'category_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(FixedAssetDepreciationSchedule::class);
    }

    public function depreciationEntries(): HasMany
    {
        return $this->hasMany(FixedAssetDepreciationEntry::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(FixedAssetTransfer::class);
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(FixedAssetDisposal::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FixedAssetTransaction::class);
    }

    public function purchaseJournal(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'purchase_journal_entry_id');
    }

    public function canDepreciate(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_FULLY_DEPRECIATED], true)
            && $this->status !== self::STATUS_FULLY_DEPRECIATED;
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_DISPOSED, self::STATUS_RETIRED], true);
    }
}

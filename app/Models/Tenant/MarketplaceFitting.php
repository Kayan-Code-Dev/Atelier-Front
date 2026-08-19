<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Marketplace\MarketplaceFittingStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceFitting extends BaseTenantModel
{
    protected $fillable = [
        'product_id',
        'branch_id',
        'customer_name',
        'customer_phone',
        'date',
        'time',
        'duration_min',
        'branch_name',
        'city',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'duration_min' => 'integer',
            'status' => MarketplaceFittingStatus::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'product_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

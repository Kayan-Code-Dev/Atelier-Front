<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Marketplace\MarketplaceReviewStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceReview extends BaseTenantModel
{
    protected $fillable = [
        'product_id',
        'order_id',
        'customer_name',
        'rating',
        'comment',
        'status',
        'reply',
        'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => MarketplaceReviewStatus::class,
            'replied_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'product_id');
    }
}

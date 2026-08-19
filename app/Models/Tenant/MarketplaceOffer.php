<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Marketplace\MarketplaceDiscountType;
use App\Enums\Marketplace\MarketplaceOfferStatus;

class MarketplaceOffer extends BaseTenantModel
{
    protected $fillable = [
        'name',
        'type',
        'value',
        'starts_at',
        'ends_at',
        'status',
        'applies_to',
    ];

    protected function casts(): array
    {
        return [
            'type' => MarketplaceDiscountType::class,
            'status' => MarketplaceOfferStatus::class,
            'value' => 'decimal:2',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }
}

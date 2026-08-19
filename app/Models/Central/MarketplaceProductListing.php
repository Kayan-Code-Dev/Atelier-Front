<?php

declare(strict_types=1);

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceProductListing extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'store_listing_id',
        'source_product_id',
        'name',
        'description',
        'category',
        'price',
        'compare_at_price',
        'image_path',
        'city',
        'area',
        'lat',
        'lng',
        'rating',
        'reviews_count',
        'condition',
        'published',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'lat' => 'float',
            'lng' => 'float',
            'rating' => 'float',
            'reviews_count' => 'integer',
            'published' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(MarketplaceStoreListing::class, 'store_listing_id');
    }
}

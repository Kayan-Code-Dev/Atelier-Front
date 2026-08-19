<?php

declare(strict_types=1);

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceStoreListing extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'slug',
        'name',
        'description',
        'phone',
        'city',
        'area',
        'lat',
        'lng',
        'radius_km',
        'covered_cities',
        'sort_by_nearest',
        'hide_outside_radius',
        'published',
        'accept_orders',
        'contact_visible',
        'logo_path',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'radius_km' => 'integer',
            'covered_cities' => 'array',
            'sort_by_nearest' => 'boolean',
            'hide_outside_radius' => 'boolean',
            'published' => 'boolean',
            'accept_orders' => 'boolean',
            'contact_visible' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(MarketplaceProductListing::class, 'store_listing_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteProductPublication extends BaseTenantModel
{
    protected $table = 'website_product_publications';

    protected $fillable = [
        'dress_id',
        'is_published',
        'is_featured',
        'sort_order',
        'site_title',
        'cta_label',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function dress(): BelongsTo
    {
        return $this->belongsTo(Dress::class, 'dress_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsitePage extends BaseTenantModel
{
    protected $table = 'website_pages';

    protected $fillable = [
        'title',
        'slug',
        'status',
        'is_visible',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
        'meta' => 'array',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(WebsiteSection::class, 'page_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteSection extends BaseTenantModel
{
    protected $table = 'website_sections';

    protected $fillable = [
        'page_id',
        'type',
        'enabled',
        'sort_order',
        'config',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sort_order' => 'integer',
        'config' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(WebsitePage::class, 'page_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteMenu extends BaseTenantModel
{
    protected $table = 'website_menus';

    protected $fillable = [
        'title',
        'url',
        'location',
        'sort_order',
        'status',
        'parent_id',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}

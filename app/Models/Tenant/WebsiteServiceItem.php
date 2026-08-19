<?php

declare(strict_types=1);

namespace App\Models\Tenant;

class WebsiteServiceItem extends BaseTenantModel
{
    protected $table = 'website_services';

    protected $fillable = [
        'name',
        'description',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}

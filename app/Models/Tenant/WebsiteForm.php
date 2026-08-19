<?php

declare(strict_types=1);

namespace App\Models\Tenant;

class WebsiteForm extends BaseTenantModel
{
    protected $table = 'website_forms';

    protected $fillable = [
        'key',
        'name',
        'is_enabled',
        'create_lead',
        'notify_to',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'create_lead' => 'boolean',
    ];
}

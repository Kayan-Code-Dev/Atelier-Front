<?php

declare(strict_types=1);

namespace App\Models\Tenant;

class WebsiteLead extends BaseTenantModel
{
    protected $table = 'website_leads';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'source',
        'campaign',
        'status',
        'assignee_user_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}

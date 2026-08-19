<?php

declare(strict_types=1);

namespace App\Models\Tenant;

class WebsiteMessage extends BaseTenantModel
{
    protected $table = 'website_messages';

    protected $fillable = [
        'sender_name',
        'sender_email',
        'sender_phone',
        'subject',
        'body',
        'status',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];
}

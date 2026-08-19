<?php

declare(strict_types=1);

namespace App\Models\Tenant;

class WebsiteMedia extends BaseTenantModel
{
    protected $table = 'website_media';

    protected $fillable = [
        'filename',
        'path',
        'mime',
        'size',
        'width',
        'height',
        'usage_label',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];
}

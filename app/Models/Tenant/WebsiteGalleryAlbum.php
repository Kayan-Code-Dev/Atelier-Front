<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsiteGalleryAlbum extends BaseTenantModel
{
    protected $table = 'website_gallery_albums';

    protected $fillable = [
        'name',
        'is_visible',
        'sort_order',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(WebsiteGalleryImage::class, 'album_id');
    }
}

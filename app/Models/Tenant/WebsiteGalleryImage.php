<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteGalleryImage extends BaseTenantModel
{
    protected $table = 'website_gallery_images';

    protected $fillable = [
        'album_id',
        'media_id',
        'alt_text',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function album(): BelongsTo
    {
        return $this->belongsTo(WebsiteGalleryAlbum::class, 'album_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(WebsiteMedia::class, 'media_id');
    }
}

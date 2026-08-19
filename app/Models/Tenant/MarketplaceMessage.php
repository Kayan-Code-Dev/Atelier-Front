<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Marketplace\MarketplaceMessageAuthor;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceMessage extends BaseTenantModel
{
    protected $fillable = [
        'thread_id',
        'author',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'author' => MarketplaceMessageAuthor::class,
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(MarketplaceThread::class, 'thread_id');
    }
}

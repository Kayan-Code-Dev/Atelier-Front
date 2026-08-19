<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Marketplace\MarketplaceThreadStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceThread extends BaseTenantModel
{
    protected $fillable = [
        'product_id',
        'customer_name',
        'customer_phone',
        'status',
        'unread_count',
        'last_message',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MarketplaceThreadStatus::class,
            'unread_count' => 'integer',
            'last_message_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'product_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MarketplaceMessage::class, 'thread_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteBookingRequest extends BaseTenantModel
{
    protected $table = 'website_booking_requests';

    protected $fillable = [
        'kind',
        'name',
        'phone',
        'email',
        'service',
        'preferred_date',
        'notes',
        'branch',
        'status',
        'amount',
        'meta',
        'lead_id',
    ];

    protected $casts = [
        'meta' => 'array',
        'amount' => 'decimal:2',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(WebsiteLead::class, 'lead_id');
    }
}

<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformBroadcast extends Model
{
    protected $connection = 'central';

    protected $table = 'platform_broadcasts';

    protected $fillable = [
        'title',
        'message',
        'target_type',
        'target_plans',
        'target_statuses',
        'channels',
        'priority',
        'status',
        'target_detail',
        'tenants_targeted',
        'tenants_delivered',
        'tenants_failed',
        'sent_by',
        'sent_at',
        'errors',
    ];

    protected function casts(): array
    {
        return [
            'target_plans' => 'array',
            'target_statuses' => 'array',
            'channels' => 'array',
            'errors' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'sent_by');
    }
}

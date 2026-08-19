<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPeriod extends BaseTenantModel
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'year',
        'month',
        'name',
        'starts_on',
        'ends_on',
        'status',
        'is_closed',
        'closed_by',
        'closed_at',
        'reopen_reason',
        'reopened_by',
        'reopened_at',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN && ! $this->is_closed;
    }

    public function blocksPosting(): bool
    {
        return $this->is_closed
            || in_array($this->status, [self::STATUS_CLOSED, self::STATUS_LOCKED], true);
    }
}

<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends BaseTenantModel
{
    public const TYPE_ASSET = 'asset';

    public const TYPE_LIABILITY = 'liability';

    public const TYPE_EQUITY = 'equity';

    public const TYPE_REVENUE = 'revenue';

    public const TYPE_EXPENSE = 'expense';

    public const NORMAL_DEBIT = 'debit';

    public const NORMAL_CREDIT = 'credit';

    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_id',
        'branch_id',
        'normal_balance',
        'is_active',
        'is_system',
        'allow_posting',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'allow_posting' => 'boolean',
    ];

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isDebitNormal(): bool
    {
        $normal = $this->normal_balance ?: (in_array($this->type, [self::TYPE_ASSET, self::TYPE_EXPENSE], true)
            ? self::NORMAL_DEBIT
            : self::NORMAL_CREDIT);

        return $normal === self::NORMAL_DEBIT;
    }

    public function allowsPosting(): bool
    {
        if ($this->allow_posting === false) {
            return false;
        }

        return (bool) $this->is_active;
    }
}

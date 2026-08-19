<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'name',
        'slug',
        'database_name',
        'status',
        'plan_id',
        'subscription_starts_at',
        'subscription_ends_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'subscription_starts_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function customSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->ofMany(['id' => 'max'], function ($query): void {
                $query->where('is_custom', true);
            });
    }

    public function currentCustomSubscription(): ?Subscription
    {
        if ($this->plan_id !== null) {
            return null;
        }

        if ($this->relationLoaded('customSubscription')) {
            return $this->customSubscription;
        }

        return $this->customSubscription()->first();
    }

    public function isOnCustomPlan(): bool
    {
        return $this->currentCustomSubscription() !== null;
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    public function provisioningLogs(): HasMany
    {
        return $this->hasMany(TenantProvisioningLog::class);
    }

    public function isDemo(): bool
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];

        return ($metadata['source'] ?? null) === 'demo';
    }

    public function wasConvertedFromDemo(): bool
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];

        return (bool) ($metadata['converted_from_demo'] ?? false);
    }

    public function hasTrialPerformanceHistory(): bool
    {
        return $this->isDemo() || $this->wasConvertedFromDemo();
    }
}

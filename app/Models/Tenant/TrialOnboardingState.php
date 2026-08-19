<?php

namespace App\Models\Tenant;

use App\Support\TrialOnboarding\TrialOnboardingStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialOnboardingState extends BaseTenantModel
{
    protected $connection = 'tenant';

    protected $fillable = [
        'user_id',
        'status',
        'current_step',
        'completed_steps',
        'viewed_steps',
        'started_at',
        'completed_at',
        'last_activity_at',
        'completion_acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_steps' => 'array',
            'viewed_steps' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'completion_acknowledged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusEnum(): TrialOnboardingStatus
    {
        return TrialOnboardingStatus::tryFrom((string) $this->status) ?? TrialOnboardingStatus::NotStarted;
    }

    /**
     * @return list<string>
     */
    public function viewedStepKeys(): array
    {
        $steps = $this->viewed_steps;

        return is_array($steps) ? array_values(array_filter(array_map('strval', $steps))) : [];
    }
}

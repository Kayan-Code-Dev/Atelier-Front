<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentApplication extends Model
{
    protected $connection = 'central';

    protected $table = 'recruitment_applications';

    protected $hidden = [
        'cv_path',
        'cv_disk',
        'portfolio_file_path',
        'ip_address',
        'user_agent',
    ];

    protected $fillable = [
        'application_number',
        'job_id',
        'full_name',
        'email',
        'phone',
        'city',
        'linkedin_url',
        'portfolio_url',
        'years_experience',
        'specialty',
        'bio',
        'cv_disk',
        'cv_path',
        'cv_original_name',
        'cv_mime',
        'cv_size',
        'portfolio_file_path',
        'portfolio_file_name',
        'consent',
        'status',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'consent' => 'boolean',
            'years_experience' => 'integer',
            'cv_size' => 'integer',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(RecruitmentJob::class, 'job_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(RecruitmentApplicationNote::class, 'application_id')->orderByDesc('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(RecruitmentApplicationEvent::class, 'application_id')->orderBy('id');
    }

    public function hasCv(): bool
    {
        return filled($this->cv_path);
    }
}

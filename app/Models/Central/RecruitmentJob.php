<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RecruitmentJob extends Model
{
    protected $connection = 'central';

    protected $table = 'recruitment_jobs';

    protected $fillable = [
        'title',
        'slug',
        'department',
        'employment_type',
        'location',
        'description',
        'responsibilities',
        'requirements',
        'nice_to_have',
        'benefits',
        'skills',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'responsibilities' => 'array',
            'requirements' => 'array',
            'nice_to_have' => 'array',
            'benefits' => 'array',
            'skills' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(RecruitmentApplication::class, 'job_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isOpenForApplications(): bool
    {
        return $this->status === 'published';
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public static function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'job';
        }

        $slug = $base;
        $i = 2;
        while (
            static::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}

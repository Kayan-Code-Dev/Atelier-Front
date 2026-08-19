<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class RecruitmentSetting extends Model
{
    protected $connection = 'central';

    protected $table = 'recruitment_settings';

    protected $fillable = [
        'notify_email',
        'accepting_applications',
        'honeypot_enabled',
        'cv_max_kilobytes',
    ];

    protected function casts(): array
    {
        return [
            'accepting_applications' => 'boolean',
            'honeypot_enabled' => 'boolean',
            'cv_max_kilobytes' => 'integer',
        ];
    }

    public static function current(): self
    {
        $row = static::query()->first();
        if ($row) {
            return $row;
        }

        return static::query()->create([
            'accepting_applications' => true,
            'honeypot_enabled' => true,
            'cv_max_kilobytes' => (int) config('recruitment.cv_max_kilobytes', 5120),
        ]);
    }
}

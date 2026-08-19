<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class LandingSetting extends Model
{
    protected $connection = 'central';

    protected $table = 'platform_landing_settings';

    protected $fillable = [
        'company_name',
        'phone',
        'whatsapp',
        'email',
        'address',
        'working_hours',
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'linkedin_url',
        'tiktok_url',
        'youtube_url',
        'footer_copyright',
        'modules',
    ];

    protected function casts(): array
    {
        return [
            'modules' => 'array',
        ];
    }
}

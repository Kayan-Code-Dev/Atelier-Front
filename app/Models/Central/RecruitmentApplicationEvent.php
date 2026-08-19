<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentApplicationEvent extends Model
{
    protected $connection = 'central';

    protected $table = 'recruitment_application_events';

    protected $fillable = [
        'application_id',
        'actor_id',
        'type',
        'from_status',
        'to_status',
        'label',
        'meta',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class, 'application_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'actor_id');
    }
}

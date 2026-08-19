<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentApplicationNote extends Model
{
    protected $connection = 'central';

    protected $table = 'recruitment_application_notes';

    protected $fillable = [
        'application_id',
        'author_id',
        'body',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class, 'application_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'author_id');
    }
}

<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmDeal extends Model
{
    use SoftDeletes;

    protected $connection = 'central';

    protected $table = 'crm_deals';

    protected $fillable = [
        'lead_id', 'title', 'lead_name', 'value', 'probability', 'temperature',
        'score', 'stage', 'assigned_to', 'next_follow_up_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'probability' => 'integer',
            'score' => 'integer',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'assigned_to');
    }
}

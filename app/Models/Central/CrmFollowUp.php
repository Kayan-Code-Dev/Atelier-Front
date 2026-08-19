<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmFollowUp extends Model
{
    protected $connection = 'central';

    protected $table = 'crm_follow_ups';

    protected $fillable = [
        'lead_id', 'kind', 'priority', 'due_at', 'reason', 'assigned_to', 'status', 'completed_at',
        'conversation_id', 'channel', 'message_intent', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
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

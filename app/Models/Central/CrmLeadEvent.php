<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmLeadEvent extends Model
{
    protected $connection = 'central';

    protected $table = 'crm_lead_events';

    protected $fillable = ['lead_id', 'type', 'title', 'body', 'meta', 'created_by'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'created_by');
    }
}

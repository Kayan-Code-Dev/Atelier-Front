<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmLeadAttachment extends Model
{
    protected $connection = 'central';

    protected $table = 'crm_lead_attachments';

    protected $fillable = ['lead_id', 'name', 'url', 'mime', 'created_by'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }
}

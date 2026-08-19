<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmLead extends Model
{
    use SoftDeletes;

    protected $connection = 'central';

    protected $table = 'crm_leads';

    protected $fillable = [
        'name', 'email', 'phone', 'whatsapp', 'facebook', 'atelier_name', 'governorate', 'activity',
        'branches_count', 'employees_count', 'source', 'score', 'temperature', 'status',
        'assigned_to', 'last_contact_at', 'next_follow_up_at', 'last_message', 'read_at',
        'offer_value', 'expected_plan', 'importance', 'close_probability', 'tenant_id', 'notes',
        'intent', 'handoff_status', 'objection', 'current_software', 'score_signals',
        'pain_points', 'purchase_intent_level', 'invoice_volume', 'identity',
    ];

    protected function casts(): array
    {
        return [
            'last_contact_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'read_at' => 'datetime',
            'offer_value' => 'decimal:2',
            'branches_count' => 'integer',
            'employees_count' => 'integer',
            'score' => 'integer',
            'close_probability' => 'integer',
            'score_signals' => 'array',
            'pain_points' => 'array',
            'invoice_volume' => 'integer',
            'identity' => 'array',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'assigned_to');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CrmLeadEvent::class, 'lead_id')->orderByDesc('id');
    }

    public function leadNotes(): HasMany
    {
        return $this->hasMany(CrmLeadNote::class, 'lead_id')->orderByDesc('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CrmLeadAttachment::class, 'lead_id')->orderByDesc('id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(CrmFollowUp::class, 'lead_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(CrmDeal::class, 'lead_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(CrmQuotation::class, 'lead_id');
    }

    public function aiSalesConversations(): HasMany
    {
        return $this->hasMany(PlatformAiSalesConversation::class, 'lead_id');
    }
}

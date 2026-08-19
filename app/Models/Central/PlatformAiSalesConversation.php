<?php

declare(strict_types=1);

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformAiSalesConversation extends Model
{
    protected $connection = 'central';

    protected $table = 'platform_ai_sales_conversations';

    protected $fillable = [
        'uuid',
        'lead_id',
        'channel',
        'external_id',
        'ownership',
        'handoff_status',
        'status',
        'intent',
        'sentiment',
        'summary',
        'last_message',
        'last_activity_at',
        'sales_state',
        'sales_memory',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'sales_memory' => 'array',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PlatformAiSalesMessage::class, 'conversation_id')->orderBy('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CrmLeadEvent::class, 'lead_id', 'lead_id');
    }
}

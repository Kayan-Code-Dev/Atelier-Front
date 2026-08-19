<?php

declare(strict_types=1);

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAiSalesMessage extends Model
{
    protected $connection = 'central';

    protected $table = 'platform_ai_sales_messages';

    protected $fillable = [
        'conversation_id',
        'author',
        'body',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(PlatformAiSalesConversation::class, 'conversation_id');
    }
}

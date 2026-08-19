<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $channel_type
 * @property string $external_message_id
 * @property string|null $from_id
 * @property string|null $text
 * @property string $status
 * @property array<string, mixed>|null $payload
 */
final class SmartAssistantInboundMessage extends Model
{
    protected $connection = 'central';

    protected $table = 'smart_assistant_inbound_messages';

    protected $fillable = [
        'tenant_id',
        'channel_type',
        'external_message_id',
        'from_id',
        'text',
        'status',
        'payload',
        'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'replied_at' => 'datetime',
        ];
    }
}

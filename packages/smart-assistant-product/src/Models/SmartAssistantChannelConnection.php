<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $user_id
 * @property string $channel_type
 * @property string|null $external_account_id
 * @property string|null $waba_id
 * @property string|null $display_name
 * @property string|null $assistant_name
 * @property int|null $department_id
 * @property string|null $department_name
 * @property string|null $session_key
 * @property string|null $access_token
 * @property string|null $webhook_verify_token
 * @property string|null $app_secret
 * @property string $status
 * @property bool $auto_reply_enabled
 * @property string $auto_reply_mode
 * @property array<string, mixed>|null $meta
 */
final class SmartAssistantChannelConnection extends Model
{
    protected $connection = 'central';

    protected $table = 'smart_assistant_channel_connections';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'channel_type',
        'external_account_id',
        'waba_id',
        'display_name',
        'assistant_name',
        'department_id',
        'department_name',
        'session_key',
        'access_token',
        'webhook_verify_token',
        'app_secret',
        'status',
        'auto_reply_enabled',
        'auto_reply_mode',
        'meta',
        'connected_at',
        'last_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'app_secret' => 'encrypted',
            'auto_reply_enabled' => 'boolean',
            'meta' => 'array',
            'connected_at' => 'datetime',
            'last_sync_at' => 'datetime',
        ];
    }
}

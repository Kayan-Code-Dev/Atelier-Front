<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $period
 * @property int $used_count
 */
final class SmartAssistantQuotaUsage extends Model
{
    protected $connection = 'central';

    protected $table = 'smart_assistant_quota_usages';

    protected $fillable = [
        'tenant_id',
        'period',
        'used_count',
    ];

    protected function casts(): array
    {
        return [
            'used_count' => 'integer',
        ];
    }
}

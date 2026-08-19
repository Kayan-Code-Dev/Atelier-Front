<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeActivityLog extends BaseTenantModel
{
    public $timestamps = false;

    protected $connection = 'tenant';

    protected $fillable = [
        'user_id',
        'employee_id',
        'actor_name',
        'module',
        'action',
        'method',
        'path',
        'title',
        'description',
        'entity_type',
        'entity_id',
        'importance',
        'meta',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
            'entity_id' => 'integer',
            'user_id' => 'integer',
            'employee_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}

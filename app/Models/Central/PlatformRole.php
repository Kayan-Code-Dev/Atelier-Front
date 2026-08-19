<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformRole extends Model
{
    protected $connection = 'central';

    protected $table = 'platform_roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            PlatformPermission::class,
            'platform_permission_role',
            'role_id',
            'permission_id',
        );
    }

    public function admins(): HasMany
    {
        return $this->hasMany(SuperAdmin::class, 'platform_role_id');
    }
}

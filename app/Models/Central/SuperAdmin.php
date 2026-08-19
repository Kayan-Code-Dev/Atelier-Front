<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class SuperAdmin extends Authenticatable
{
    use HasApiTokens;

    protected $connection = 'central';

    protected $table = 'super_admins';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar_data',
        'password',
        'status',
        'platform_role_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'avatar_data',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(PlatformRole::class, 'platform_role_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuperRole(): bool
    {
        $role = $this->relationLoaded('role') ? $this->role : $this->role()->first();

        return $role !== null && $role->is_system && $role->slug === 'super-admin';
    }

    /**
     * Legacy admins without a role keep full access until assigned.
     */
    public function hasFullAccess(): bool
    {
        if ($this->platform_role_id === null) {
            return true;
        }

        return $this->isSuperRole();
    }

    public function hasPermission(string $permissionKey): bool
    {
        if ($this->hasFullAccess()) {
            return true;
        }

        $role = $this->relationLoaded('role') ? $this->role : $this->role()->with('permissions')->first();
        if ($role === null) {
            return false;
        }

        if (! $role->relationLoaded('permissions')) {
            $role->load('permissions');
        }

        return $role->permissions->contains(fn (PlatformPermission $p): bool => $p->key === $permissionKey);
    }

    /**
     * @return list<string>
     */
    public function permissionKeys(): array
    {
        if ($this->hasFullAccess()) {
            return \App\Support\PlatformPermissionCatalog::keys();
        }

        $role = $this->relationLoaded('role') ? $this->role : $this->role()->with('permissions')->first();
        if ($role === null) {
            return [];
        }

        if (! $role->relationLoaded('permissions')) {
            $role->load('permissions');
        }

        return $role->permissions->pluck('key')->values()->all();
    }
}

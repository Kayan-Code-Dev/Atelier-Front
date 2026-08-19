<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'branch_id',
        'avatar_path',
        'avatar_data',
        'status',
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
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hrEmployee(): HasOne
    {
        return $this->hasOne(HrEmployee::class, 'user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function isOwner(): bool
    {
        return $this->roles()->where('slug', 'owner')->exists();
    }
}

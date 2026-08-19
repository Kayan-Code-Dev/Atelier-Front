<?php

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $permissions = $this->relationLoaded('permissions')
            ? $this->permissions->pluck('key')->values()->all()
            : [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'is_system' => (bool) $this->is_system,
            'permissions' => $permissions,
            'users_count' => (int) ($this->admins_count ?? 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

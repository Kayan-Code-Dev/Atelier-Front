<?php

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuperAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->relationLoaded('role') && $this->platform_role_id) {
            $this->resource->load(['role.permissions']);
        }

        $role = $this->relationLoaded('role') ? $this->role : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatarUrl(),
            'status' => $this->status,
            'platform_role_id' => $this->platform_role_id,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'role' => $role ? [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'color' => $role->color,
                'is_system' => (bool) $role->is_system,
            ] : null,
            'permissions' => $this->permissionKeys(),
            'has_full_access' => $this->hasFullAccess(),
        ];
    }

    private function avatarUrl(): ?string
    {
        $data = $this->avatar_data ?? null;
        if (is_string($data) && trim($data) !== '') {
            return $data;
        }

        return null;
    }
}

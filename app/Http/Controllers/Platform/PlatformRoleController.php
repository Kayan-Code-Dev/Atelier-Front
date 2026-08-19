<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Resources\Platform\PlatformRoleResource;
use App\Models\Central\PlatformPermission;
use App\Models\Central\PlatformRole;
use App\Support\ApiResponse;
use App\Support\PlatformPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformRoleController extends Controller
{
    public function permissions(): JsonResponse
    {
        $rows = PlatformPermission::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (PlatformPermission $p): array => [
                'id' => $p->id,
                'key' => $p->key,
                'name' => $p->name,
                'module' => $p->module,
                'sort_order' => $p->sort_order,
            ])
            ->all();

        $groups = collect($rows)->groupBy('module')->map(function ($items, $module) {
            return [
                'module' => $module,
                'permissions' => $items->values()->all(),
            ];
        })->values()->all();

        return ApiResponse::success([
            'permissions' => $rows,
            'groups' => $groups,
            'keys' => PlatformPermissionCatalog::keys(),
        ]);
    }

    public function index(): JsonResponse
    {
        $roles = PlatformRole::query()
            ->with('permissions')
            ->withCount('admins')
            ->orderByDesc('is_system')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(PlatformRoleResource::collection($roles)->resolve());
    }

    public function show(int $id): JsonResponse
    {
        $role = PlatformRole::query()->with('permissions')->withCount('admins')->findOrFail($id);

        return ApiResponse::success(new PlatformRoleResource($role));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:32'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(PlatformPermissionCatalog::keys())],
        ]);

        $slug = Str::slug($data['name']);
        if ($slug === '' || PlatformRole::query()->where('slug', $slug)->exists()) {
            $slug = $slug.'-'.Str::lower(Str::random(4));
        }

        $role = PlatformRole::query()->create([
            'name' => trim($data['name']),
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? 'teal',
            'is_system' => false,
        ]);

        $this->syncPermissions($role, $data['permissions'] ?? []);

        return ApiResponse::success(
            new PlatformRoleResource($role->load('permissions')->loadCount('admins')),
            'Role created',
            201,
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $role = PlatformRole::query()->findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:32'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(PlatformPermissionCatalog::keys())],
        ]);

        if ($role->is_system && array_key_exists('name', $data)) {
            unset($data['name']);
        }

        $role->fill([
            'name' => $data['name'] ?? $role->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $role->description,
            'color' => $data['color'] ?? $role->color,
        ]);
        $role->save();

        if (array_key_exists('permissions', $data)) {
            $this->syncPermissions($role, $data['permissions'] ?? []);
        }

        return ApiResponse::success(
            new PlatformRoleResource($role->load('permissions')->loadCount('admins')),
            'Role updated',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $role = PlatformRole::query()->withCount('admins')->findOrFail($id);

        if ($role->is_system) {
            return ApiResponse::error('لا يمكن حذف أدوار النظام', 422);
        }

        if ((int) $role->admins_count > 0) {
            return ApiResponse::error('لا يمكن حذف دور مرتبط بمستخدمين', 422);
        }

        $role->permissions()->detach();
        $role->delete();

        return ApiResponse::success(null, 'Role deleted');
    }

    /**
     * @param  list<string>  $keys
     */
    private function syncPermissions(PlatformRole $role, array $keys): void
    {
        $ids = PlatformPermission::query()
            ->whereIn('key', $keys)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($ids);
    }
}

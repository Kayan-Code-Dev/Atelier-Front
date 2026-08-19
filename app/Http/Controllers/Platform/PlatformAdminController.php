<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Resources\Platform\SuperAdminResource;
use App\Models\Central\PlatformRole;
use App\Models\Central\SuperAdmin;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlatformAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 50)));

        $query = SuperAdmin::query()
            ->with('role')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->query('search')).'%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)->orWhere('email', 'like', $search);
            });
        }

        $admins = $query->paginate($perPage);

        return ApiResponse::paginated(
            $admins,
            SuperAdminResource::collection($admins->items())->resolve(),
        );
    }

    public function show(int $id): JsonResponse
    {
        $admin = SuperAdmin::query()->with('role.permissions')->findOrFail($id);

        return ApiResponse::success(new SuperAdminResource($admin));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique(SuperAdmin::class, 'email')],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'platform_role_id' => ['required', 'integer', Rule::exists(PlatformRole::class, 'id')],
            'status' => ['nullable', Rule::in(['active', 'suspended'])],
        ]);

        $admin = SuperAdmin::query()->create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => $data['password'],
            'platform_role_id' => (int) $data['platform_role_id'],
            'status' => $data['status'] ?? 'active',
        ]);

        return ApiResponse::success(
            new SuperAdminResource($admin->load('role.permissions')),
            'Admin created',
            201,
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $admin = SuperAdmin::query()->findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email', 'max:190', Rule::unique(SuperAdmin::class, 'email')->ignore($admin->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'platform_role_id' => ['sometimes', 'integer', Rule::exists(PlatformRole::class, 'id')],
            'status' => ['sometimes', Rule::in(['active', 'suspended'])],
        ]);

        if ($request->user()?->id === $admin->id && ($data['status'] ?? null) === 'suspended') {
            return ApiResponse::error('لا يمكنك تعليق حسابك الحالي', 422);
        }

        if (array_key_exists('name', $data)) {
            $admin->name = trim($data['name']);
        }
        if (array_key_exists('email', $data)) {
            $admin->email = strtolower(trim($data['email']));
        }
        if (! empty($data['password'])) {
            $admin->password = $data['password'];
        }
        if (array_key_exists('platform_role_id', $data)) {
            $admin->platform_role_id = (int) $data['platform_role_id'];
        }
        if (array_key_exists('status', $data)) {
            $admin->status = $data['status'];
        }
        $admin->save();

        return ApiResponse::success(
            new SuperAdminResource($admin->load('role.permissions')),
            'Admin updated',
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $admin = SuperAdmin::query()->findOrFail($id);

        if ($request->user()?->id === $admin->id) {
            return ApiResponse::error('لا يمكنك حذف حسابك الحالي', 422);
        }

        $activeCount = SuperAdmin::query()->where('status', 'active')->count();
        if ($admin->status === 'active' && $activeCount <= 1) {
            return ApiResponse::error('لا يمكن حذف آخر مسؤول نشط', 422);
        }

        $admin->tokens()->delete();
        $admin->delete();

        return ApiResponse::success(null, 'Admin deleted');
    }

    public function suspend(Request $request, int $id): JsonResponse
    {
        return $this->setStatus($request, $id, 'suspended');
    }

    public function activate(Request $request, int $id): JsonResponse
    {
        return $this->setStatus($request, $id, 'active');
    }

    public function changePassword(Request $request): JsonResponse
    {
        /** @var SuperAdmin $admin */
        $admin = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $admin->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['كلمة المرور الحالية غير صحيحة.'],
            ]);
        }

        $admin->password = $data['new_password'];
        $admin->save();

        return ApiResponse::success(null, 'تم تغيير كلمة المرور');
    }

    private function setStatus(Request $request, int $id, string $status): JsonResponse
    {
        $admin = SuperAdmin::query()->findOrFail($id);

        if ($request->user()?->id === $admin->id && $status === 'suspended') {
            return ApiResponse::error('لا يمكنك تعليق حسابك الحالي', 422);
        }

        if ($status === 'suspended') {
            $activeCount = SuperAdmin::query()->where('status', 'active')->count();
            if ($admin->status === 'active' && $activeCount <= 1) {
                return ApiResponse::error('لا يمكن تعليق آخر مسؤول نشط', 422);
            }
        }

        $admin->status = $status;
        $admin->save();

        if ($status === 'suspended') {
            $admin->tokens()->delete();
        }

        return ApiResponse::success(
            new SuperAdminResource($admin->load('role.permissions')),
            $status === 'active' ? 'تم تفعيل الحساب' : 'تم تعليق الحساب',
        );
    }
}

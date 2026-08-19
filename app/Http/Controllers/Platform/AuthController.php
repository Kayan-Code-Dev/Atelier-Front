<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\LoginRequest;
use App\Http\Resources\Platform\SuperAdminResource;
use App\Models\Central\SuperAdmin;
use App\Services\Auth\PlatformAuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(private readonly PlatformAuthService $platformAuthService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->platformAuthService->login(
            $request->string('email')->toString(),
            $request->string('password')->toString()
        );

        return ApiResponse::success([
            'token' => $result['token'],
            'user' => new SuperAdminResource($result['admin']),
        ], 'Platform login successful');
    }

    public function me(Request $request): JsonResponse
    {
        $admin = $request->user();
        $admin?->load(['role.permissions']);

        return ApiResponse::success([
            'user' => new SuperAdminResource($admin),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logged out');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var SuperAdmin $admin */
        $admin = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email', 'max:190', Rule::unique(SuperAdmin::class, 'email')->ignore($admin->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        if (array_key_exists('name', $data)) {
            $admin->name = trim($data['name']);
        }
        if (array_key_exists('email', $data)) {
            $admin->email = strtolower(trim($data['email']));
        }
        if (array_key_exists('phone', $data)) {
            $phone = trim((string) ($data['phone'] ?? ''));
            $admin->phone = $phone !== '' ? $phone : null;
        }
        $admin->save();

        $admin->load(['role.permissions']);

        return ApiResponse::success(
            ['user' => new SuperAdminResource($admin)],
            'تم تحديث الملف الشخصي',
        );
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        /** @var SuperAdmin $admin */
        $admin = $request->user();

        $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'avatar.required' => 'الصورة مطلوبة.',
            'avatar.image' => 'الملف يجب أن يكون صورة.',
            'avatar.max' => 'حجم الصورة يجب ألا يتجاوز 5 ميجابايت.',
        ]);

        $file = $request->file('avatar');
        $mime = strtolower((string) ($file->getMimeType() ?: 'image/jpeg'));
        if (! str_starts_with($mime, 'image/')) {
            return ApiResponse::validation(['avatar' => ['نوع الصورة غير صالح.']]);
        }

        $binary = file_get_contents($file->getRealPath());
        if ($binary === false || $binary === '') {
            return ApiResponse::error('تعذر قراءة الصورة', 422);
        }

        $admin->avatar_data = 'data:'.$mime.';base64,'.base64_encode($binary);
        $admin->save();
        $admin->load(['role.permissions']);

        return ApiResponse::success(
            ['user' => new SuperAdminResource($admin)],
            'تم تحديث الصورة الشخصية',
        );
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        /** @var SuperAdmin $admin */
        $admin = $request->user();
        $admin->avatar_data = null;
        $admin->save();
        $admin->load(['role.permissions']);

        return ApiResponse::success(
            ['user' => new SuperAdminResource($admin)],
            'تم حذف الصورة الشخصية',
        );
    }
}

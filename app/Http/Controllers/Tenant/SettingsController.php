<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\DeleteAccountRequest;
use App\Http\Requests\Tenant\Settings\ResetTenantDataRequest;
use App\Http\Requests\Tenant\Settings\UpdateAppSettingsRequest;
use App\Http\Requests\Tenant\Settings\UpdatePasswordRequest;
use App\Http\Requests\Tenant\Settings\UpdateProfileRequest;
use App\Http\Requests\Tenant\Settings\UploadAvatarRequest;
use App\Http\Resources\Tenant\UserResource;
use App\Models\Tenant\User;
use App\Services\Tenant\AppSettingService;
use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantDataResetService;
use App\Services\Tenant\TenantUserAvatarService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class SettingsController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantUserAvatarService $avatarService,
        private readonly AppSettingService $appSettingService,
        private readonly TenantDataResetService $dataResetService,
    ) {}

    public function appSettings(): JsonResponse
    {
        return ApiResponse::success($this->appSettingService->present());
    }

    public function updateAppSettings(UpdateAppSettingsRequest $request): JsonResponse
    {
        $settings = $this->appSettingService->update($request->validated());

        return ApiResponse::success($settings, 'App settings updated');
    }

    public function profile(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->validated());
        $user->save();

        return ApiResponse::success(new UserResource($user->refresh()), 'Profile updated');
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $tenant = $this->tenantContext->requireTenant();
        $user = $request->user();
        $this->avatarService->assertTenantContext($tenant);

        if (! $request->hasFile('avatar')) {
            return ApiResponse::error('لم يتم إرسال ملف الصورة', 422);
        }

        try {
            $previousPath = $user->avatar_path;
            $dataUrl = $this->avatarService->storeAsDataUrl($request->file('avatar'));

            $user->avatar_data = $dataUrl;
            $user->avatar_path = null;
            $user->save();

            $this->avatarService->deleteIfOwned($tenant, $previousPath);
        } catch (\Throwable $exception) {
            report($exception);

            return ApiResponse::error('تعذر حفظ الصورة. تحقق من حجم الملف أو نوعه.', 500);
        }

        return ApiResponse::success(new UserResource($user->refresh()), 'Avatar updated');
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->string('current_password')->toString(), (string) $user->password)) {
            return ApiResponse::error('Current password is incorrect', 422);
        }

        $user->password = $request->string('password')->toString();
        $user->save();

        return ApiResponse::success(null, 'Password updated');
    }

    public function deleteAccount(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->string('password')->toString(), (string) $user->password)) {
            return ApiResponse::error('Password is incorrect', 422);
        }

        $tenant = $this->tenantContext->tenant();
        if ($tenant !== null) {
            $this->avatarService->deleteIfOwned($tenant, $user->avatar_path);
        }

        $user->tokens()->delete();
        $user->status = 'deleted';
        $user->email = 'deleted+'.$user->id.'+'.Str::random(8).'@deleted.local';
        $user->name = 'Deleted User';
        $user->avatar_path = null;
        $user->avatar_data = null;
        $user->save();

        return ApiResponse::success(null, 'Account deleted');
    }

    public function dataResetPreview(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return ApiResponse::unauthorized();
        }

        try {
            return ApiResponse::success($this->dataResetService->preview($user));
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'OWNER_REQUIRED') {
                return ApiResponse::forbidden('تصفير البيانات متاح لمالك الحساب فقط');
            }

            throw $exception;
        }
    }

    public function resetData(ResetTenantDataRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return ApiResponse::unauthorized();
        }

        if (! Hash::check($request->string('password')->toString(), (string) $user->password)) {
            return ApiResponse::error('كلمة المرور غير صحيحة', 422);
        }

        $confirmation = trim($request->string('confirmation')->toString());
        if ($confirmation !== TenantDataResetService::CONFIRMATION_WORD) {
            return ApiResponse::error('اكتب كلمة «تصفير» للتأكيد', 422);
        }

        try {
            $preview = $this->dataResetService->reset($user);
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'OWNER_REQUIRED') {
                return ApiResponse::forbidden('تصفير البيانات متاح لمالك الحساب فقط');
            }

            throw $exception;
        }

        return ApiResponse::success($preview, 'تم تصفير بيانات الحساب. الحساب أصبح كحساب جديد.');
    }
}

<?php

namespace App\Services\Auth;

use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantDatabaseManager;
use App\Services\Tenant\TenantUserDirectoryService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TenantAuthService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantUserDirectoryService $tenantUserDirectoryService,
        private readonly TenantDatabaseManager $tenantDatabaseManager,
        private readonly GoogleIdTokenVerifier $googleIdTokenVerifier,
    ) {}

    public function login(string $email, string $password): array
    {
        $normalizedEmail = strtolower(trim($email));

        if ($normalizedEmail === '') {
            throw ValidationException::withMessages([
                'email' => ['بيانات الدخول غير صحيحة.'],
            ]);
        }

        $tenant = $this->tenantUserDirectoryService->findTenantByEmail($normalizedEmail);

        if ($tenant === null) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الدخول غير صحيحة.'],
            ]);
        }

        $this->assertTenantCanAuthenticate($tenant);

        $this->tenantContext->setTenant($tenant);
        $this->tenantDatabaseManager->connect($tenant);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الدخول غير صحيحة.'],
            ]);
        }

        if ((string) $user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['هذا الحساب غير نشط.'],
            ]);
        }

        return $this->buildAuthPayload($user, $tenant, 'tenant-token');
    }

    /**
     * Google Identity Services — ID token → same Sanctum session as password login.
     *
     * @return array{token: string, user: User, tenant: Tenant, permissions: list<string>, plan: mixed}
     */
    public function loginWithGoogleIdToken(string $idToken): array
    {
        $identity = $this->googleIdTokenVerifier->verify($idToken);
        $normalizedEmail = $identity['email'];

        $tenant = $this->tenantUserDirectoryService->findTenantByEmail($normalizedEmail);

        if ($tenant === null) {
            throw ValidationException::withMessages([
                'id_token' => ['لا يوجد حساب مسجّل بهذا البريد في DressnMore. تواصل مع الإدارة لإضافتك أولاً.'],
            ]);
        }

        $this->assertTenantCanAuthenticate($tenant);

        $this->tenantContext->setTenant($tenant);
        $this->tenantDatabaseManager->connect($tenant);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'id_token' => ['الحساب غير موجود داخل الورشة المرتبطة بهذا البريد.'],
            ]);
        }

        if ((string) $user->status !== 'active') {
            throw ValidationException::withMessages([
                'id_token' => ['هذا الحساب غير نشط.'],
            ]);
        }

        return $this->buildAuthPayload($user, $tenant, 'google-token');
    }

    /**
     * @return array{token: string, user: User, tenant: Tenant, permissions: list<string>, plan: mixed}
     */
    private function buildAuthPayload(User $user, Tenant $tenant, string $tokenName): array
    {
        $permissions = $this->permissionsForUser($user);
        $user->loadMissing(['hrEmployee.branch', 'hrEmployee.branches']);

        return [
            'token' => $this->issueTenantToken($user, $tenant, $tokenName),
            'user' => $user,
            'tenant' => $tenant->loadMissing('plan'),
            'permissions' => $permissions,
            'plan' => $tenant->plan,
        ];
    }

    /**
     * Platform admin "login as" — issue a tenant owner session without password.
     *
     * @return array{token: string, user: User, tenant: Tenant, permissions: list<string>, plan: mixed}
     */
    public function loginAsTenantOwner(Tenant $tenant): array
    {
        $this->assertTenantCanAuthenticate($tenant);

        $this->tenantContext->setTenant($tenant);
        $this->tenantDatabaseManager->connect($tenant);

        $metadata = is_array($tenant->metadata) ? $tenant->metadata : [];
        $adminEmail = strtolower(trim((string) ($metadata['admin_email'] ?? '')));

        $user = null;
        if ($adminEmail !== '') {
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [$adminEmail])
                ->where('status', 'active')
                ->first();
        }

        if ($user === null) {
            $user = User::query()
                ->where('status', 'active')
                ->orderBy('id')
                ->first();
        }

        if ($user === null) {
            throw ValidationException::withMessages([
                'tenant' => ['لا يوجد مستخدم نشط داخل هذا التينانت للدخول.'],
            ]);
        }

        $permissions = $this->permissionsForUser($user);

        return [
            'token' => $this->issueTenantToken($user, $tenant, 'platform-login-as'),
            'user' => $user,
            'tenant' => $tenant->loadMissing('plan'),
            'permissions' => $permissions,
            'plan' => $tenant->plan,
        ];
    }

    public function issueTenantToken(User $user, Tenant $tenant, string $tokenName = 'tenant-token'): string
    {
        $tokenResult = $user->createToken($tokenName);
        $tokenResult->accessToken->forceFill(['tenant_id' => $tenant->id])->save();

        return $tokenResult->plainTextToken;
    }

    /**
     * @return list<string>
     */
    public function permissionsForUser(User $user): array
    {
        return $user->roles()
            ->with('permissions:id,key')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('key')
            ->unique()
            ->values()
            ->all();
    }

    private function assertTenantCanAuthenticate(Tenant $tenant): void
    {
        $status = (string) $tenant->status;
        if ($status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['هذا الحساب غير متاح حالياً.'],
            ]);
        }

        if ($tenant->subscription_ends_at !== null) {
            $endsAt = CarbonImmutable::parse((string) $tenant->subscription_ends_at);
            if ($endsAt->lt(CarbonImmutable::today())) {
                throw ValidationException::withMessages([
                    'email' => ['انتهى تفعيل الحساب. يرجى التواصل معنا لتجديد الاشتراك.'],
                ]);
            }
        }
    }
}

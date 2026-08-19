<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\GoogleLoginRequest;
use App\Http\Requests\Tenant\LoginRequest;
use App\Http\Resources\Tenant\UserResource;
use App\Models\Central\Tenant;
use App\Services\Auth\TenantAuthService;
use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use App\Support\TenantSubscriptionPresenter;
use DressnMore\Platform\Application\AiAccessGate;
use DressnMore\SmartAssistantProduct\Application\SmartAssistantAccessGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly TenantAuthService $tenantAuthService,
        private readonly TenantContext $tenantContext,
        private readonly TenantSubscriptionPresenter $tenantSubscriptionPresenter,
        private readonly AiAccessGate $aiAccessGate,
        private readonly SmartAssistantAccessGate $smartAssistantAccessGate,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->tenantAuthService->login(
            $request->string('email')->toString(),
            $request->string('password')->toString()
        );

        return $this->loginSuccessResponse($request, $result, 'Tenant login successful');
    }

    public function loginWithGoogle(GoogleLoginRequest $request): JsonResponse
    {
        $result = $this->tenantAuthService->loginWithGoogleIdToken(
            $request->string('id_token')->toString()
        );

        return $this->loginSuccessResponse($request, $result, 'Google login successful');
    }

    public function googleConfig(): JsonResponse
    {
        $clientId = trim((string) config('services.google.client_id', ''));

        return ApiResponse::success([
            'enabled' => $clientId !== '',
            'client_id' => $clientId !== '' ? $clientId : null,
        ]);
    }

    /**
     * @param  array{token: string, user: mixed, tenant: Tenant, permissions: list<string>}  $result
     */
    private function loginSuccessResponse(Request $request, array $result, string $message): JsonResponse
    {
        $tenant = $result['tenant'];
        $slug = (string) $tenant->slug;
        $baseDomains = config('tenancy.domain.base_domains', ['dressnmore.it.com']);
        $baseDomain = is_array($baseDomains) && $baseDomains !== []
            ? (string) $baseDomains[0]
            : 'dressnmore.it.com';
        $frontendHost = strtolower($slug.'.'.$baseDomain);
        $apiOrigin = rtrim((string) config('app.url', 'https://api.dressnmore.it.com'), '/');
        $backendApiUrl = $apiOrigin.'/api/tenant';

        $requestHost = strtolower((string) $request->getHost());
        $isStaging = app()->environment('staging')
            || str_contains($apiOrigin, 'staging')
            || str_contains($requestHost, 'staging')
            || str_contains(base_path(), 'back-staging')
            || str_contains(base_path(), 'staging');

        // Staging hub hosts the FE; keep users on the hub origin after login.
        if ($isStaging) {
            $frontendHost = 'staging-tenant.dressnmore.it.com';
            $backendApiUrl = 'https://staging-api.dressnmore.it.com/api/tenant';
        }

        $navigation = [
            'ai_assistant' => ['visible' => false, 'items' => []],
            'smart_assistant' => ['visible' => false, 'items' => []],
        ];
        try {
            $navigation = [
                'ai_assistant' => $this->aiAccessGate->navigationPayload($tenant, $result['permissions']),
                'smart_assistant' => $this->smartAssistantAccessGate->navigationPayload($tenant, $result['permissions']),
            ];
        } catch (\Throwable) {
            // Login must succeed even if optional nav gates fail.
        }

        return ApiResponse::success([
            'token' => $result['token'],
            'user' => new UserResource($result['user']),
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $slug,
            ],
            'permissions' => $result['permissions'],
            'subscription' => $this->tenantSubscriptionPresenter->forTenant($tenant),
            'navigation' => $navigation,
            'endpoints' => [
                'frontend_app_url' => 'https://'.$frontendHost,
                'backend_api_url' => $backendApiUrl,
                'backend_api_origin' => preg_replace('#/api/(?:tenant|v1)$#', '', $backendApiUrl) ?: $apiOrigin,
                'reverb_public_url' => (string) config('reverb.public_url', ''),
            ],
        ], $message);
    }

    public function me(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->tenant();
        $user = $request->user();
        $permissions = $this->tenantAuthService->permissionsForUser($user);
        $user?->loadMissing(['hrEmployee.branch', 'hrEmployee.branches']);

        return ApiResponse::success([
            'user' => new UserResource($user),
            'tenant' => [
                'id' => $this->tenantContext->id(),
                'name' => $tenant?->name,
                'slug' => $this->tenantContext->slug(),
            ],
            'permissions' => $permissions,
            'subscription' => $this->tenantSubscriptionPresenter->forTenant($tenant),
            'navigation' => [
                'ai_assistant' => $tenant !== null
                    ? $this->aiAccessGate->navigationPayload($tenant, $permissions)
                    : ['visible' => false, 'items' => []],
                'smart_assistant' => $tenant !== null
                    ? $this->smartAssistantAccessGate->navigationPayload($tenant, $permissions)
                    : ['visible' => false, 'items' => []],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logged out');
    }
}

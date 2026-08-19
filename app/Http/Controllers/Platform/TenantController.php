<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\Tenant\AddDomainRequest;
use App\Http\Requests\Platform\Tenant\RenewTenantRequest;
use App\Http\Requests\Platform\Tenant\SeedTenantRequest;
use App\Http\Requests\Platform\Tenant\StoreTenantRequest;
use App\Http\Requests\Platform\Tenant\UpdateTenantRequest;
use App\Http\Resources\Platform\TenantDomainResource;
use App\Http\Resources\Platform\TenantResource;
use App\Models\Central\Tenant;
use App\Models\Central\TenantDomain;
use App\Services\Platform\TenantProvisioningService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantProvisioningService $tenantProvisioningService,
        private readonly \App\Services\Auth\TenantAuthService $tenantAuthService,
        private readonly \App\Support\TenantSubscriptionPresenter $tenantSubscriptionPresenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));
        $tenants = $this->tenantProvisioningService->paginate([
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'plan_id' => $request->query('plan_id'),
        ], $perPage);

        return ApiResponse::paginated($tenants, TenantResource::collection($tenants->items())->resolve());
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load(['plan', 'domains', 'customSubscription']);

        return ApiResponse::success(new TenantResource($tenant));
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $data = $request->validated();
        $admin = $request->user();
        $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
        if ($admin) {
            $metadata['created_by_admin_id'] = (int) $admin->id;
            $metadata['created_by_admin_name'] = (string) $admin->name;
            $metadata['created_by_admin_email'] = strtolower(trim((string) $admin->email));
        }
        $data['metadata'] = $metadata;

        $tenant = $this->tenantProvisioningService->create($data);

        return ApiResponse::success(new TenantResource($tenant), 'Tenant created', 201);
    }

    /**
     * Open the tenant app as the atelier owner (SSO handoff).
     */
    public function loginAs(Tenant $tenant): JsonResponse
    {
        try {
            $result = $this->tenantAuthService->loginAsTenantOwner($tenant);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: $exception->getMessage();

            return ApiResponse::error((string) $message, 422);
        } catch (Throwable $exception) {
            return ApiResponse::serverError('تعذر الدخول للتينانت: '.$exception->getMessage());
        }

        $slug = (string) $result['tenant']->slug;
        $apiOrigin = rtrim((string) config('app.url', 'https://api.dressnmore.it.com'), '/');
        $backendApiUrl = $apiOrigin.'/api/tenant';
        $frontendUrl = rtrim((string) (env('FRONTEND_URL') ?: 'https://dressnmore.it.com'), '/');

        $isStaging = app()->environment('staging')
            || str_contains($apiOrigin, 'staging')
            || str_contains(base_path(), 'back-staging')
            || str_contains(base_path(), 'staging');

        if ($isStaging) {
            $frontendUrl = 'https://staging-tenant.dressnmore.it.com';
            $backendApiUrl = 'https://staging-api.dressnmore.it.com/api/tenant';
        }

        $payload = [
            'token' => $result['token'],
            'user' => (new \App\Http\Resources\Tenant\UserResource($result['user']))->resolve(),
            'tenant' => [
                'id' => $result['tenant']->id,
                'name' => $result['tenant']->name,
                'slug' => $slug,
            ],
            'permissions' => $result['permissions'],
            'subscription' => $this->tenantSubscriptionPresenter->forTenant($result['tenant']),
            'endpoints' => [
                'frontend_app_url' => $frontendUrl,
                'backend_api_url' => $backendApiUrl,
                'backend_api_origin' => preg_replace('#/api/(?:tenant|v1)$#', '', $backendApiUrl) ?: $apiOrigin,
            ],
        ];

        $encoded = base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $handoffUrl = $frontendUrl.'/auth/handoff#'.$encoded;

        return ApiResponse::success([
            'handoff_url' => $handoffUrl,
            'login_url' => $frontendUrl.'/login',
            'tenant' => [
                'id' => $result['tenant']->id,
                'name' => $result['tenant']->name,
                'slug' => $slug,
            ],
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
            ],
        ], 'تم إنشاء رابط الدخول للتينانت');
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant = $this->tenantProvisioningService->update($tenant, $request->validated());

        return ApiResponse::success(new TenantResource($tenant), 'Tenant updated');
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->tenantProvisioningService->destroy($tenant);

        return ApiResponse::success(null, 'Tenant deleted');
    }

    public function migrate(Tenant $tenant): JsonResponse
    {
        try {
            $tenant = $this->tenantProvisioningService->migrate($tenant);
        } catch (RuntimeException $exception) {
            return ApiResponse::serverError($exception->getMessage());
        }

        return ApiResponse::success(new TenantResource($tenant), 'Tenant migrated');
    }

    public function seed(SeedTenantRequest $request, Tenant $tenant): JsonResponse
    {
        try {
            $credentials = $this->tenantProvisioningService->seedAdmin($tenant, $request->validated());
        } catch (Throwable $exception) {
            return ApiResponse::serverError('Tenant seed failed: '.$exception->getMessage());
        }

        $tenant->refresh()->load(['plan', 'domains']);

        return ApiResponse::success([
            ...$credentials,
            'tenant' => (new TenantResource($tenant))->resolve(),
        ], 'Tenant admin seeded');
    }

    public function addDomain(AddDomainRequest $request, Tenant $tenant): JsonResponse
    {
        $domain = $this->tenantProvisioningService->addDomain($tenant, (string) $request->validated('domain'));

        return ApiResponse::success(new TenantDomainResource($domain), 'Domain added', 201);
    }

    public function deleteDomain(Tenant $tenant, TenantDomain $domain): JsonResponse
    {
        try {
            $this->tenantProvisioningService->deleteDomain($tenant, $domain);
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 404);
        }

        return ApiResponse::success(null, 'Domain deleted');
    }

    public function suspend(Tenant $tenant): JsonResponse
    {
        $tenant = $this->tenantProvisioningService->suspend($tenant);

        return ApiResponse::success(new TenantResource($tenant), 'Tenant suspended');
    }

    public function activate(Tenant $tenant): JsonResponse
    {
        $tenant = $this->tenantProvisioningService->activate($tenant);

        return ApiResponse::success(new TenantResource($tenant), 'Tenant activated');
    }

    public function renew(RenewTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant = $this->tenantProvisioningService->renew($tenant, $request->validated());

        return ApiResponse::success(new TenantResource($tenant), 'Tenant renewed');
    }
}

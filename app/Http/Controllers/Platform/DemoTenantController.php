<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\DemoTenant\PromoteDemoTenantRequest;
use App\Http\Requests\Platform\DemoTenant\StoreDemoTenantRequest;
use App\Http\Resources\Platform\PaymentResource;
use App\Http\Resources\Platform\SubscriptionResource;
use App\Http\Resources\Platform\TenantResource;
use App\Models\Central\Tenant;
use App\Services\Platform\DemoTenantService;
use App\Services\Platform\TrialPerformanceQueryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DemoTenantController extends Controller
{
    public function __construct(
        private readonly DemoTenantService $demoTenantService,
        private readonly TrialPerformanceQueryService $performanceQuery,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));
        $tenants = $this->demoTenantService->paginate([
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'lifecycle' => 'active',
        ], $perPage);

        return ApiResponse::paginated($tenants, TenantResource::collection($tenants->items())->resolve());
    }

    public function expired(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));
        $tenants = $this->demoTenantService->paginate([
            'search' => $request->query('search'),
            'lifecycle' => 'expired',
        ], $perPage);

        return ApiResponse::paginated($tenants, TenantResource::collection($tenants->items())->resolve());
    }

    public function store(StoreDemoTenantRequest $request): JsonResponse
    {
        $admin = $request->user();
        try {
            $result = $this->demoTenantService->createAndProvision(
                $request->validated(),
                [
                    'id' => $admin?->id,
                    'name' => $admin?->name,
                    'email' => $admin?->email,
                ],
            );
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success([
            'tenant' => new TenantResource($result['tenant']),
            'admin' => $result['admin'],
            'login_url' => $result['login_url'],
            'hostname_label' => $result['hostname_label'],
            'demo_days' => $result['demo_days'],
            'subscription_ends_at' => $result['subscription_ends_at'],
            'warning' => 'احفظ كلمة المرور الآن — تُعرض مرة واحدة فقط. أُنشئ الحساب بدون باقة وينتهي تلقائياً بعد المدة المحددة.',
        ], 'تم إنشاء حساب الديمو التجريبي بنجاح', 201);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $metadata = is_array($tenant->metadata) ? $tenant->metadata : [];
        if (($metadata['source'] ?? null) !== 'demo') {
            return ApiResponse::notFound('Demo tenant not found');
        }

        $tenant->load(['plan', 'domains']);

        return ApiResponse::success(new TenantResource($tenant));
    }

    public function performance(Request $request, Tenant $tenant): JsonResponse
    {
        if (! $tenant->hasTrialPerformanceHistory()) {
            return ApiResponse::notFound('Trial not found');
        }

        $perPage = max(1, min(50, $request->integer('per_page', (int) config('trial_performance.activity_per_page', 30))));

        return ApiResponse::success($this->performanceQuery->build($tenant, [
            'category' => (string) $request->query('category', 'all'),
            'period' => (string) $request->query('period', 'all'),
            'page' => max(1, $request->integer('page', 1)),
            'per_page' => $perPage,
        ]));
    }

    public function promote(PromoteDemoTenantRequest $request, Tenant $tenant): JsonResponse
    {
        try {
            $result = $this->demoTenantService->promoteToSubscription($tenant, $request->validated());
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success([
            'tenant' => new TenantResource($result['tenant']),
            'subscription' => new SubscriptionResource($result['subscription']),
            'payment' => $result['payment'] ? new PaymentResource($result['payment']) : null,
        ], 'تم تحويل الحساب التجريبي إلى اشتراك بنجاح');
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        try {
            $this->demoTenantService->destroy($tenant);
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success(null, 'تم حذف الحساب التجريبي بنجاح');
    }
}

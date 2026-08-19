<?php

namespace App\Http\Middleware;

use App\Services\Platform\PlanEntitlementService;
use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use App\Support\PlanFeatureCatalog;
use App\Support\TenantMessages;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanFeature
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PlanEntitlementService $entitlements,
    ) {}

    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $tenant = $this->tenantContext->tenant();

        if ($tenant === null) {
            return ApiResponse::error(TenantMessages::CONTEXT_REQUIRED, 400);
        }

        if ($tenant->plan === null && ! $tenant->isOnCustomPlan() && ! $tenant->isDemo() && ! app()->environment('testing')) {
            return ApiResponse::forbidden('Plan is not assigned', [
                'code' => 'plan_not_assigned',
                'recommended_plan' => PlanFeatureCatalog::PLAN_FREE,
            ]);
        }

        if (! $this->entitlements->canAccess($tenant, $featureKey)) {
            $payload = $this->entitlements->denyPayload($tenant, $featureKey);

            return ApiResponse::forbidden(
                $payload['upgrade_message'] ?? 'Feature is not available',
                $payload,
            );
        }

        return $next($request);
    }
}

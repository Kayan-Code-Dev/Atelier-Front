<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistantProduct\Http\Middleware;

use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use Closure;
use DressnMore\SmartAssistantProduct\Application\SmartAssistantAccessGate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSmartAssistantEnabled
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SmartAssistantAccessGate $accessGate,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenantContext->tenant();
        if ($tenant === null) {
            return ApiResponse::error('Tenant context required', 400);
        }

        if (! $this->accessGate->isFeatureVisible($tenant)) {
            return ApiResponse::forbidden('المساعد الذكي غير متاح لهذه الباقة');
        }

        return $next($request);
    }
}

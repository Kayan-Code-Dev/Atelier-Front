<?php

declare(strict_types=1);

namespace DressnMore\Platform\Http\Middleware;

use App\Services\Tenant\TenantContext;
use App\Support\ApiResponse;
use Closure;
use DressnMore\Platform\Application\AiAccessGate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects AI routes when module / package / tenant feature is off.
 * Permission middleware remains separate.
 */
final class EnsureAiFeatureEnabled
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AiAccessGate $aiAccessGate,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenantContext->tenant();
        if ($tenant === null) {
            return ApiResponse::error('Tenant context required', 400);
        }

        if (! $this->aiAccessGate->isFeatureVisible($tenant)) {
            return ApiResponse::forbidden('AI Assistant is not available for this tenant');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Central\SuperAdmin;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlatformPermission
{
    public function handle(Request $request, Closure $next, string ...$permissionKeys): Response
    {
        $user = $request->user();

        if (! $user instanceof SuperAdmin) {
            return ApiResponse::forbidden('Platform admin access required');
        }

        if (! $user->isActive()) {
            return ApiResponse::forbidden('Account suspended');
        }

        foreach ($permissionKeys as $permissionKey) {
            if ($user->hasPermission($permissionKey)) {
                return $next($request);
            }
        }

        return ApiResponse::forbidden('ليس لديك صلاحية لتنفيذ هذا الإجراء');
    }
}

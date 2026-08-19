<?php

namespace App\Http\Middleware;

use App\Services\Tenant\EmployeeActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogEmployeeActivity
{
    public function __construct(private readonly EmployeeActivityLogger $logger) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $this->logger->logFromRequest($request, $response->getStatusCode());
        } catch (\Throwable) {
            // never break the request because of logging
        }

        return $response;
    }
}

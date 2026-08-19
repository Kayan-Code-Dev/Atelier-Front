<?php

use App\Http\Middleware\CheckDressCategoryPlanFeature;
use App\Http\Middleware\CheckPlanFeature;
use App\Http\Middleware\CheckPlatformPermission;
use App\Http\Middleware\CheckTenantPermission;
use App\Http\Middleware\CheckTenantSubscription;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureTenantTokenBinding;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\SetTenantDatabase;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'platform.admin' => EnsurePlatformAdmin::class,
            'platform.permission' => CheckPlatformPermission::class,
            'identify.tenant' => IdentifyTenant::class,
            'identify.public.website' => \App\Http\Middleware\IdentifyPublicWebsiteTenant::class,
            'check.tenant.subscription' => CheckTenantSubscription::class,
            'set.tenant.database' => SetTenantDatabase::class,
            'ensure.tenant.token' => EnsureTenantTokenBinding::class,
            'tenant.permission' => CheckTenantPermission::class,
            'plan.feature' => CheckPlanFeature::class,
            'plan.dress_category' => CheckDressCategoryPlanFeature::class,
            'log.employee.activity' => \App\Http\Middleware\LogEmployeeActivity::class,
        ]);

        $middleware->prependToPriorityList(AuthenticatesRequests::class, SetTenantDatabase::class);
        $middleware->prependToPriorityList(SetTenantDatabase::class, CheckTenantSubscription::class);
        $middleware->prependToPriorityList(CheckTenantSubscription::class, IdentifyTenant::class);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('tenants:process-expiry')->hourly();
        $schedule->command('tenants:send-operational-reminders')->dailyAt('08:00');
        $schedule->command('smart-assistant:atelier-whatsapp-reminders')->dailyAt('09:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                $translatedErrors = [];
                foreach ($exception->errors() as $field => $messages) {
                    $translatedErrors[$field] = array_map(
                        static fn (string $message): string => __($message),
                        $messages,
                    );
                }

                return ApiResponse::error(
                    message: 'The given data was invalid.',
                    status: 422,
                    errors: $translatedErrors,
                );
            }

            if ($exception instanceof AuthenticationException) {
                return ApiResponse::unauthorized();
            }

            if ($exception instanceof AuthorizationException) {
                return ApiResponse::forbidden();
            }

            if ($exception instanceof ModelNotFoundException) {
                return ApiResponse::notFound('Resource not found');
            }

            if ($exception instanceof HttpExceptionInterface) {
                $httpMessage = trim((string) $exception->getMessage());

                return ApiResponse::error(
                    message: $httpMessage !== '' ? $httpMessage : 'Request failed',
                    status: $exception->getStatusCode(),
                );
            }

            report($exception);

            return ApiResponse::serverError();
        });
    })->create();

<?php

declare(strict_types=1);

namespace DressnMore\Platform;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Platform\Application\AiAccessGate;
use DressnMore\Platform\Http\Middleware\EnsureAiFeatureEnabled;
use DressnMore\Platform\Module\AiIntegrationModule;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

final class DressnMorePlatformServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dressnmore-platform.php', 'dressnmore-platform');

        $this->app->singleton(AiAccessGate::class);
        $this->app->singleton(AiIntegrationModule::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/dressnmore-platform.php' => config_path('dressnmore-platform.php'),
        ], 'dressnmore-platform-config');

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('ai.feature', EnsureAiFeatureEnabled::class);

        $this->registerModule();
        $this->loadRoutes();
    }

    private function registerModule(): void
    {
        if (! $this->app->bound(ModuleRegistryInterface::class)) {
            return;
        }

        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        if (! $registry->has('platform.ai-integration')) {
            $registry->add($this->app->make(AiIntegrationModule::class));
        }
    }

    private function loadRoutes(): void
    {
        if (! $this->app->routesAreCached()) {
            // Host app mounts these inside the authenticated tenant group via Route::group include,
            // or we load with full middleware prefix here.
            // Prefer explicit load from routes/api/tenant.php to keep middleware stack consistent.
        }
    }
}

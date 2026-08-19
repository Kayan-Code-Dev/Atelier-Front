<?php

namespace App\Providers;

use App\Console\Commands\TenantHealthCommand;
use App\Contracts\AiSales\DressnMoreSalesContext;
use App\Contracts\Marketplace\MarketplaceListingSyncPortInterface;
use App\Events\TrialOnboarding\TrialOnboardingProgressed;
use App\Listeners\TrialOnboarding\RecordTrialOnboardingEventListener;
use App\Models\Central\PersonalAccessToken;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbox;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Dress;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\Supplier;
use App\Observers\TrialOnboardingObserver;
use App\Services\Platform\AiSales\DressnMoreSalesContextBuilder;
use App\Services\Platform\AiSales\Tools\DressnMoreSalesToolBootstrap;
use App\Services\Platform\Marketplace\MarketplaceListingSyncService;
use App\Services\Tenant\TenantContext;
use DressnMore\Aos\Tools\Application\ToolGateway;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, fn () => new TenantContext);
        $this->app->singleton(DressnMoreSalesContext::class, DressnMoreSalesContextBuilder::class);
        $this->app->singleton(MarketplaceListingSyncPortInterface::class, MarketplaceListingSyncService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Event::listen(TrialOnboardingProgressed::class, RecordTrialOnboardingEventListener::class);

        $observer = TrialOnboardingObserver::class;
        Branch::observe($observer);
        Cashbox::observe($observer);
        Supplier::observe($observer);
        PurchaseOrder::observe($observer);
        Customer::observe($observer);
        Invoice::observe($observer);
        Dress::observe($observer);

        if ($this->app->bound(ToolGateway::class)) {
            try {
                $this->app->make(DressnMoreSalesToolBootstrap::class)
                    ->register($this->app->make(ToolGateway::class));
            } catch (\Throwable) {
                // Sales tools remain available via DressnMoreSalesTools.
            }
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                TenantHealthCommand::class,
                \App\Console\Commands\SyncAiSalesKnowledgeCommand::class,
            ]);
        }
    }
}

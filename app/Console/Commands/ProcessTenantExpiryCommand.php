<?php

namespace App\Console\Commands;

use App\Services\Platform\DemoTenantService;
use App\Services\Platform\SubscriptionAdminService;
use Illuminate\Console\Command;

class ProcessTenantExpiryCommand extends Command
{
    protected $signature = 'tenants:process-expiry';

    protected $description = 'Expire tenants past subscription_ends_at and notify demo/trial users';

    public function handle(
        DemoTenantService $demoTenantService,
        SubscriptionAdminService $subscriptionAdminService,
    ): int {
        $demoResult = $demoTenantService->processExpiry();
        $subscriptionAdminService->syncMissingFromTenants();
        $subResult = $subscriptionAdminService->processExpiry();

        $this->info(sprintf(
            'Tenants expired: %d | Notified: %d | Subscriptions expired: %d',
            $demoResult['expired'],
            $demoResult['notified'],
            $subResult['expired'],
        ));

        return self::SUCCESS;
    }
}

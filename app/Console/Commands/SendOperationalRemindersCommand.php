<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Services\Tenant\OperationalNotificationService;
use App\Services\Tenant\TenantContext;
use App\Services\Tenant\TenantDatabaseManager;
use Illuminate\Console\Command;
use Throwable;

class SendOperationalRemindersCommand extends Command
{
    protected $signature = 'tenants:send-operational-reminders {--tenant= : Tenant slug only}';

    protected $description = 'Send in-app reminders for deliveries/returns due tomorrow and overdue returns';

    public function handle(
        TenantDatabaseManager $tenantDatabaseManager,
        TenantContext $tenantContext,
        OperationalNotificationService $operationalNotifications,
    ): int {
        $slug = $this->option('tenant');
        $tenants = Tenant::query()
            ->when($slug, fn ($q) => $q->where('slug', $slug))
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->warn('No active tenants found.');

            return self::SUCCESS;
        }

        $totals = ['delivery' => 0, 'return' => 0, 'tailoring' => 0, 'overdue' => 0, 'tenants' => 0];

        foreach ($tenants as $tenant) {
            try {
                $tenantContext->setTenant($tenant);
                $tenantDatabaseManager->connect($tenant);
                $counts = $operationalNotifications->sendDueTomorrowReminders();
                $totals['delivery'] += $counts['delivery'];
                $totals['return'] += $counts['return'];
                $totals['tailoring'] += $counts['tailoring'];
                $totals['overdue'] += $counts['overdue'];
                $totals['tenants']++;
                $this->line(sprintf(
                    '%s: delivery=%d return=%d tailoring=%d overdue=%d',
                    $tenant->slug,
                    $counts['delivery'],
                    $counts['return'],
                    $counts['tailoring'],
                    $counts['overdue'],
                ));
            } catch (Throwable $e) {
                $this->error("Failed for {$tenant->slug}: {$e->getMessage()}");
            }
        }

        $this->info(sprintf(
            'Done. tenants=%d delivery=%d return=%d tailoring=%d overdue=%d',
            $totals['tenants'],
            $totals['delivery'],
            $totals['return'],
            $totals['tailoring'],
            $totals['overdue'],
        ));

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Central\Plan;
use App\Models\Central\Tenant;
use Database\Seeders\Central\PlanFeatureSeeder;
use Database\Seeders\Central\PlanSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SyncPlgPlansCommand extends Command
{
    protected $signature = 'plans:sync-plg
        {--remap-tenants : Remap legacy tenants forward to free/starter/professional/business}
        {--restore-legacy : Remap tenants on inactive PLG plans back to basic/pro/enterprise}';

    protected $description = 'Seed plan matrix; by default keeps legacy plans active and PLG plans inactive';

    public function handle(): int
    {
        $this->info('Seeding plans...');
        Artisan::call('db:seed', [
            '--database' => 'central',
            '--class' => PlanSeeder::class,
            '--force' => true,
        ]);
        $this->line(trim(Artisan::output()));

        $this->info('Syncing feature matrix...');
        Artisan::call('db:seed', [
            '--database' => 'central',
            '--class' => PlanFeatureSeeder::class,
            '--force' => true,
        ]);
        $this->line(trim(Artisan::output()));

        // Default: restore tenants that were moved onto inactive PLG plans.
        if ($this->option('restore-legacy') || ! $this->option('remap-tenants')) {
            $map = [
                'free' => 'basic',
                'starter' => 'basic',
                'professional' => 'pro',
                'business' => 'enterprise',
            ];

            foreach ($map as $from => $to) {
                $fromPlan = Plan::query()->where('slug', $from)->first();
                $toPlan = Plan::query()->where('slug', $to)->first();
                if (! $fromPlan || ! $toPlan) {
                    continue;
                }

                $updated = Tenant::query()
                    ->where('plan_id', $fromPlan->id)
                    ->update(['plan_id' => $toPlan->id]);

                if ($updated > 0) {
                    $this->line("Restored {$updated} tenants from {$from} → {$to}");
                }
            }
        }

        if ($this->option('remap-tenants')) {
            $map = [
                'basic' => 'starter',
                'pro' => 'professional',
                'enterprise' => 'business',
            ];

            foreach ($map as $from => $to) {
                $fromPlan = Plan::query()->where('slug', $from)->first();
                $toPlan = Plan::query()->where('slug', $to)->first();
                if (! $fromPlan || ! $toPlan) {
                    continue;
                }

                $updated = Tenant::query()
                    ->where('plan_id', $fromPlan->id)
                    ->update(['plan_id' => $toPlan->id]);

                $this->line("Remapped {$updated} tenants from {$from} → {$to}");
            }
        }

        $this->info('Plans sync completed.');

        return self::SUCCESS;
    }
}

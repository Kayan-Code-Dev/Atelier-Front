<?php

declare(strict_types=1);

namespace DressnMore\Platform\Database\Seeders;

use DressnMore\Platform\Module\AiIntegrationModule;
use DressnMore\Platform\Support\AiPermissionCatalog;
use Illuminate\Database\Seeder;

/**
 * Documents / validates AI module metadata for seed pipelines.
 * Permission/plan seeding is applied via App seeders that consume the catalogs.
 */
final class AiModuleSeeder extends Seeder
{
    public function run(): void
    {
        /** @var AiIntegrationModule $module */
        $module = app(AiIntegrationModule::class);
        $this->command?->info('AI module: '.$module->name().' v'.$module->version().' enabled='.($module->isEnabled() ? 'yes' : 'no'));
        $this->command?->info('AI permissions: '.implode(', ', AiPermissionCatalog::keys()));
    }
}

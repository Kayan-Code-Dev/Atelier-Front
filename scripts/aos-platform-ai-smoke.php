<?php

declare(strict_types=1);

/**
 * Smoke test for DressnMore Platform AI Integration (Sprint 18A).
 * Run: php scripts/aos-platform-ai-smoke.php
 */

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Platform\Domain\AiNavigation;
use DressnMore\Platform\Module\AiIntegrationModule;
use DressnMore\Platform\Support\AiPermissionCatalog;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Route;

require __DIR__.'/../vendor/autoload.php';

$failed = 0;
$assertTrue = static function (bool $cond, string $label) use (&$failed): void {
    echo ($cond ? "  OK  " : " FAIL ").$label."\n";
    if (! $cond) {
        $failed++;
    }
};

echo "DressnMore Platform AI — domain smoke\n";

$assertTrue(count(AiPermissionCatalog::keys()) === 7, '7 AI permissions');
$assertTrue(count(AiNavigation::items()) === 6, '6 AI nav items');
$assertTrue(AiNavigation::forPermissions([]) === [], 'empty perms → empty nav');
$assertTrue(count(AiNavigation::forPermissions(['ai.access'])) === 6, 'ai.access unlocks nav');

echo "DressnMore Platform AI — Laravel wiring\n";

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/** @var ModuleRegistryInterface $modules */
$modules = $app->make(ModuleRegistryInterface::class);
$assertTrue($modules->has('platform.ai-integration'), 'module registered');

/** @var AiIntegrationModule $module */
$module = $app->make(AiIntegrationModule::class);
$assertTrue($module->version() === '0.18.5', 'module version 0.18.5');
$assertTrue($module->isEnabled(), 'module enabled');

$assertTrue(
    (bool) config('aos.feature_flags.ai_platform_integration') === true,
    'feature flag ai_platform_integration'
);
$assertTrue(
    in_array('ai_assistant.enabled', \App\Support\PlanFeatureCatalog::keys(), true),
    'plan catalog has AI Assistant'
);
$assertTrue(
    array_key_exists('ai.access', \App\Support\PermissionLabels::all()),
    'PermissionLabels has ai.access'
);

$routeNames = collect(Route::getRoutes())->map(static fn ($r) => $r->uri())->all();
$assertTrue(in_array('api/tenant/ai', $routeNames, true) || in_array('tenant/ai', $routeNames, true), 'route tenant/ai');
$assertTrue(
    collect($routeNames)->contains(static fn ($u) => str_contains((string) $u, 'tenant/ai/history')),
    'route tenant/ai/history'
);

echo $failed === 0 ? "PASSED\n" : "FAILED ({$failed})\n";
exit($failed === 0 ? 0 : 1);

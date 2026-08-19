<?php

declare(strict_types=1);

/**
 * Sprint 1 smoke validation (no PHPUnit) for AOS Foundation boot.
 * Usage: php scripts/aos-foundation-smoke.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use DressnMore\Aos\Core\Configuration\Contracts\ConfigurationProviderInterface;
use DressnMore\Aos\Core\Kernel\Contracts\KernelInterface;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Observability\Contracts\HealthReporterInterface;

$kernel = $app->make(KernelInterface::class);
$registry = $app->make(ModuleRegistryInterface::class);
$config = $app->make(ConfigurationProviderInterface::class);
$bus = $app->make(EventBusInterface::class);
$health = $app->make(HealthReporterInterface::class)->report();

$checks = [
    'kernel_ready' => $kernel->isReady(),
    'state_ready' => $kernel->state() === 'ready',
    'module_core' => $registry->has('aos.core'),
    'module_events' => $registry->has('aos.events'),
    'module_observability' => $registry->has('aos.observability'),
    'feature_business_off' => $config->isFeatureEnabled('business_tools') === false,
    'feature_ai_off' => $config->isFeatureEnabled('ai_providers') === false,
    'feature_whatsapp_off' => $config->isFeatureEnabled('channels_whatsapp') === false,
    'event_bus_bound' => $bus instanceof EventBusInterface,
    'health_ok' => ($health['healthy'] ?? false) === true,
];

$failed = array_filter($checks, static fn (bool $ok): bool => ! $ok);

foreach ($checks as $name => $ok) {
    echo sprintf("[%s] %s\n", $ok ? 'OK' : 'FAIL', $name);
}

if ($failed !== []) {
    fwrite(STDERR, "AOS Foundation smoke FAILED\n");
    exit(1);
}

echo "AOS Foundation smoke PASSED\n";
exit(0);

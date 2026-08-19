<?php

declare(strict_types=1);

/**
 * Smoke test for AOS Business Tool Gateway (Sprint 4).
 * Run: php scripts/aos-tools-smoke.php
 */

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Tools\Application\ToolGateway;
use DressnMore\Aos\Tools\Application\ToolPipelineFactory;
use DressnMore\Aos\Tools\Domain\Discovery\ToolDiscovery;
use DressnMore\Aos\Tools\Domain\Executor\ToolExecutor;
use DressnMore\Aos\Tools\Domain\Factories\ToolRequestFactory;
use DressnMore\Aos\Tools\Domain\Registry\ToolRegistry;
use DressnMore\Aos\Tools\Domain\Resolver\ToolResolver;
use DressnMore\Aos\Tools\Domain\Result\ExecutionStatus;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;
use DressnMore\Aos\Tools\Domain\Tool\ToolOperatingMode;
use DressnMore\Aos\Tools\Domain\Validator\ConceptualToolValidator;
use DressnMore\Aos\Tools\Infrastructure\Authorization\CapabilityAuthorizationHook;
use DressnMore\Aos\Tools\Infrastructure\Handlers\EchoStubToolHandler;
use DressnMore\Aos\Tools\Infrastructure\Hooks\InMemoryAnalyticsHook;
use DressnMore\Aos\Tools\Infrastructure\Hooks\InMemoryAuditHook;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

require __DIR__.'/../vendor/autoload.php';

$failed = 0;
$assertTrue = static function (bool $cond, string $label) use (&$failed): void {
    if ($cond) {
        echo "  OK  {$label}\n";
    } else {
        echo " FAIL {$label}\n";
        $failed++;
    }
};

echo "AOS Tools Gateway — domain smoke\n";

$registry = new ToolRegistry();
$discovery = new ToolDiscovery($registry);
$resolver = new ToolResolver($registry);
$executor = new ToolExecutor($registry);
$pipeline = (new ToolPipelineFactory(
    $registry,
    $discovery,
    $resolver,
    new ConceptualToolValidator(),
    new CapabilityAuthorizationHook(),
    $executor,
    new InMemoryAuditHook(),
    new InMemoryAnalyticsHook(),
))->create();

$bus = new class implements EventBusInterface {
    public function publish(object $event): void {}

    public function subscribe(string $event, string|callable $listener): void {}
};

$gateway = new ToolGateway($registry, $discovery, $pipeline, $bus);
$gateway->register(new EchoStubToolHandler());
$factory = new ToolRequestFactory();

$assertTrue($gateway->registry()->has(ToolIdentifier::fromString('EchoStub')), 'register EchoStub');
$assertTrue(count($gateway->discovery()->all()) === 1, 'discovery lists tool');

$ok = $gateway->execute($factory->make(
    'EchoStub',
    ['message' => 'hi'],
    ToolOperatingMode::Assistant,
    ['tools.echo.execute'],
    ['tools.echo'],
));
$assertTrue($ok->status() === ExecutionStatus::Success, 'pipeline success');
$assertTrue(($ok->payload()['echo'] ?? null) === 'hi', 'echo payload');

$unknown = $gateway->execute($factory->make(
    'DoesNotExist',
    [],
    ToolOperatingMode::Assistant,
    ['tools.echo.execute'],
    ['tools.echo'],
));
$assertTrue($unknown->status() === ExecutionStatus::NotFound, 'unknown tool');

$invalid = $gateway->execute($factory->make(
    'EchoStub',
    [],
    ToolOperatingMode::Assistant,
    ['tools.echo.execute'],
    ['tools.echo'],
));
$assertTrue($invalid->status() === ExecutionStatus::ValidationFailed, 'validation failed');

echo "AOS Tools Gateway — Laravel wiring\n";

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/** @var ModuleRegistryInterface $modules */
$modules = $app->make(ModuleRegistryInterface::class);
$assertTrue($modules->has('aos.tools'), 'module aos.tools registered');

/** @var ToolGateway $appGateway */
$appGateway = $app->make(ToolGateway::class);
$assertTrue($appGateway instanceof ToolGateway, 'ToolGateway bound');

if ($failed === 0) {
    echo "PASSED\n";
    exit(0);
}

echo "FAILED ({$failed})\n";
exit(1);

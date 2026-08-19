<?php

declare(strict_types=1);

/**
 * Smoke test for AOS Permission & Policy Engine (Sprint 5).
 * Run: php scripts/aos-permissions-smoke.php
 */

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Permissions\Application\AuthorizationManager;
use DressnMore\Aos\Permissions\Application\AuthorizationPipelineFactory;
use DressnMore\Aos\Permissions\Application\PermissionEngineFacade;
use DressnMore\Aos\Permissions\Domain\Approval\ApprovalEngine;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityEngine;
use DressnMore\Aos\Permissions\Domain\Capability\CapabilityRegistry;
use DressnMore\Aos\Permissions\Domain\Decision\AuthorizationOutcome;
use DressnMore\Aos\Permissions\Domain\Decision\DecisionEngine;
use DressnMore\Aos\Permissions\Domain\Factories\AuthorizationRequestFactory;
use DressnMore\Aos\Permissions\Domain\Mode\OperatingModeManager;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionEngine;
use DressnMore\Aos\Permissions\Domain\Permission\PermissionRegistry;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyEngine;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyRegistry;
use DressnMore\Aos\Permissions\Domain\Policy\PolicyResolver;
use DressnMore\Aos\Permissions\Domain\Risk\RiskEvaluator;
use DressnMore\Aos\Permissions\Infrastructure\Bootstrap\BuiltinCatalogBootstrap;
use DressnMore\Aos\Permissions\Infrastructure\Persistence\InMemoryApprovalRepository;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

require __DIR__.'/../vendor/autoload.php';

$failed = 0;
$assertTrue = static function (bool $cond, string $label) use (&$failed): void {
    echo ($cond ? "  OK  " : " FAIL ").$label."\n";
    if (! $cond) {
        $failed++;
    }
};

echo "AOS Permissions — domain smoke\n";

$capabilities = new CapabilityRegistry();
$permissions = new PermissionRegistry();
$policies = new PolicyRegistry();
(new BuiltinCatalogBootstrap($capabilities, $permissions))->seed();

$capabilityEngine = new CapabilityEngine($capabilities);
$permissionEngine = new PermissionEngine($permissions);
$modeManager = new OperatingModeManager();
$policyEngine = new PolicyEngine($policies);
$approvalEngine = new ApprovalEngine(new InMemoryApprovalRepository());
$pipeline = (new AuthorizationPipelineFactory(
    $capabilityEngine,
    $permissionEngine,
    $modeManager,
    $policyEngine,
    new PolicyResolver($policies),
    new RiskEvaluator(),
    new DecisionEngine($modeManager),
    $approvalEngine,
))->create();

$bus = new class implements EventBusInterface {
    public function publish(object $event): void {}

    public function subscribe(string $event, string|callable $listener): void {}
};

$manager = new AuthorizationManager($pipeline, $approvalEngine, $bus);
$factory = new AuthorizationRequestFactory();

$ok = $manager->authorize($factory->make(
    'read_customer',
    'assistant',
    ['read_customer'],
    ['perm.read_customer'],
));
$assertTrue($ok->outcome() === AuthorizationOutcome::Authorized, 'authorize read_customer');

$denied = $manager->authorize($factory->make(
    'read_customer',
    'assistant',
    [],
    ['perm.read_customer'],
));
$assertTrue($denied->outcome() === AuthorizationOutcome::Denied, 'deny missing capability');

$approval = $manager->authorize($factory->make(
    'cancel_reservation',
    'hybrid',
    ['cancel_reservation'],
    ['perm.cancel_reservation'],
));
$assertTrue($approval->outcome() === AuthorizationOutcome::ApprovalRequired, 'approval required for cancel');
$assertTrue($approval->approvalRequestId() !== null, 'approval request created');

$human = $manager->authorize($factory->make(
    'read_invoice',
    'human_only',
    ['read_invoice'],
    ['perm.read_invoice'],
));
$assertTrue($human->outcome() === AuthorizationOutcome::HumanEscalation, 'human_only escalates');

echo "AOS Permissions — Laravel wiring\n";

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/** @var ModuleRegistryInterface $modules */
$modules = $app->make(ModuleRegistryInterface::class);
$assertTrue($modules->has('aos.permissions'), 'module aos.permissions registered');

/** @var PermissionEngineFacade $facade */
$facade = $app->make(PermissionEngineFacade::class);
$seeded = $facade->authorizeCapability(
    'read_knowledge',
    'assistant',
    ['read_knowledge'],
    ['perm.read_knowledge'],
);
$assertTrue($seeded->outcome() === AuthorizationOutcome::Authorized, 'facade authorizes seeded capability');

echo $failed === 0 ? "PASSED\n" : "FAILED ({$failed})\n";
exit($failed === 0 ? 0 : 1);

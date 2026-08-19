<?php

declare(strict_types=1);

/**
 * Smoke test for AOS Platform Planner (Sprint 18).
 * Run: php scripts/aos-planner-platform-smoke.php
 */

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Planner\Application\Platform\CapabilityMatcher;
use DressnMore\Aos\Planner\Application\Platform\ExecutionPlanBuilder;
use DressnMore\Aos\Planner\Application\Platform\IntentAnalyzer;
use DressnMore\Aos\Planner\Application\Platform\PermissionValidator;
use DressnMore\Aos\Planner\Application\Platform\PlatformPlannerEngine;
use DressnMore\Aos\Planner\Application\Platform\PolicyEvaluator;
use DressnMore\Aos\Planner\Application\Platform\SubscriptionValidator;
use DressnMore\Aos\Planner\Application\Platform\ToolSelector;
use DressnMore\Aos\Planner\Contracts\PlannerInterface;
use DressnMore\Aos\Planner\Domain\Platform\PlatformPlanningContext;
use DressnMore\Aos\Planner\Domain\Platform\PlanningStatus;
use DressnMore\Aos\Planner\Infrastructure\InMemoryExecutionPlanRepository;
use DressnMore\Aos\Planner\Module\PlannerModule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

require __DIR__.'/../vendor/autoload.php';

$failed = 0;
$assertTrue = static function (bool $cond, string $label) use (&$failed): void {
    echo ($cond ? "  OK  " : " FAIL ").$label."\n";
    if (! $cond) {
        $failed++;
    }
};

$bus = new class implements EventBusInterface {
    public function publish(object $event): void {}

    public function subscribe(string $event, string|callable $listener): void {}
};

$ctx = static function (
    string $message,
    string $subscription = 'professional',
    array $grantedTools = [],
    array $availableTools = [],
): PlatformPlanningContext {
    return new PlatformPlanningContext(
        $message,
        'tenant_demo',
        'conv_1',
        'user_1',
        'branch_1',
        'ar',
        $subscription,
        [],
        [],
        $grantedTools,
        $availableTools,
        [],
        'corr_smoke',
    );
};

echo "AOS Platform Planner — Sprint 18 smoke\n";

$engine = PlatformPlannerEngine::createDefault($bus);

$book = $engine->plan($ctx('احجز فستان'));
$assertTrue($book->intent() === 'BookReservation', 'book intent');
$assertTrue($book->status() === PlanningStatus::RequiresApproval, 'book requires approval');
$assertTrue(in_array('CreateReservation', $book->selectedTools(), true), 'book selects CreateReservation');

$customer = $engine->plan($ctx('أضف عميل'));
$assertTrue($customer->intent() === 'CreateCustomer', 'create customer intent');

$sales = $engine->plan($ctx('كم مبيعات اليوم', 'professional'));
$assertTrue($sales->intent() === 'SalesSummary', 'sales intent');
$assertTrue($sales->status() === PlanningStatus::Ready, 'sales ready');

$unknown = $engine->plan($ctx('xyz nonsense 12345'));
$assertTrue($unknown->status() === PlanningStatus::Rejected, 'unknown rejected');

$sub = $engine->plan($ctx('كم مبيعات اليوم', 'basic'));
$assertTrue($sub->status() === PlanningStatus::Rejected, 'basic subscription blocks report');
$assertTrue(str_contains($sub->rejectionReason(), 'subscription_denied'), 'subscription reason');

$perm = $engine->plan($ctx('احجز فستان', 'professional', ['CheckAvailability']));
$assertTrue($perm->status() === PlanningStatus::Rejected, 'permission denied');

$capEngine = PlatformPlannerEngine::createDefault($bus, ['Customer.Search']);
$cap = $capEngine->plan($ctx('احجز فستان'));
$assertTrue($cap->status() === PlanningStatus::Rejected, 'missing capability rejected');

$policyEngine = new PlatformPlannerEngine(
    new IntentAnalyzer(),
    new CapabilityMatcher(),
    new ToolSelector(),
    new PolicyEvaluator(['CreateReservation']),
    new PermissionValidator(),
    new SubscriptionValidator(),
    new ExecutionPlanBuilder(),
    new InMemoryExecutionPlanRepository(),
    $bus,
);
$blocked = $policyEngine->plan($ctx('احجز فستان'));
$assertTrue($blocked->status() === PlanningStatus::Rejected, 'policy blocked tool');
$assertTrue(str_contains($blocked->rejectionReason(), 'tool_policy_blocked'), 'policy reason');

echo "AOS Platform Planner — Laravel wiring\n";

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/** @var PlannerInterface $bound */
$bound = $app->make(PlannerInterface::class);
$assertTrue($bound instanceof PlatformPlannerEngine, 'PlannerInterface → PlatformPlannerEngine');

/** @var PlannerModule $module */
$module = $app->make(PlannerModule::class);
$assertTrue($module->version() === '0.18.0', 'module version 0.18.0');

$wired = $bound->plan($ctx('أضف عميل'));
$assertTrue($wired->intent() === 'CreateCustomer', 'laravel platform plan');

echo $failed === 0 ? "PASSED\n" : "FAILED ({$failed})\n";
exit($failed === 0 ? 0 : 1);

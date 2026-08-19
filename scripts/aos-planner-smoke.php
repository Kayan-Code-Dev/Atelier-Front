<?php

declare(strict_types=1);

/**
 * Smoke test for AOS AI Planner (Sprint 6).
 * Run: php scripts/aos-planner-smoke.php
 */

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Planner\Application\PlannerEngine;
use DressnMore\Aos\Planner\Domain\Context\PlanningContext;
use DressnMore\Aos\Planner\Domain\Intent\IntentKind;
use DressnMore\Aos\Planner\Domain\Plan\PlanningDecision;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

require __DIR__.'/../vendor/autoload.php';

$failed = 0;
$assertTrue = static function (bool $cond, string $label) use (&$failed): void {
    echo ($cond ? "  OK  " : " FAIL ").$label."\n";
    if (! $cond) {
        $failed++;
    }
};

echo "AOS Planner — domain smoke\n";

$bus = new class implements EventBusInterface {
    public function publish(object $event): void {}

    public function subscribe(string $event, string|callable $listener): void {}
};
$engine = PlannerEngine::createDefault($bus);

$single = $engine->plan(PlanningContext::fromMessage('أريد معرفة المتبقي عليّ'));
$assertTrue($single->intentKind() === IntentKind::Single, 'single intent');
$assertTrue($single->decision() === PlanningDecision::ReadyToExecute, 'single ready');
$assertTrue(in_array('GetOutstandingBalance', $single->toolCandidates(), true), 'balance tool candidate');

$multi = $engine->plan(PlanningContext::fromMessage('أريد معرفة المتبقي عليّ وأحجز بروفة'));
$assertTrue($multi->intentKind() === IntentKind::Multi, 'multi intent');
$assertTrue($multi->isExecutable(), 'multi executable');

$conflict = $engine->plan(PlanningContext::fromMessage('أحجز موعد وألغي الحجز'));
$assertTrue($conflict->intentKind() === IntentKind::Conflicting, 'conflicting intent');
$assertTrue($conflict->decision() === PlanningDecision::ClarificationRequired, 'conflict clarifies');

$unknown = $engine->plan(PlanningContext::fromMessage('zzzz qqqq'));
$assertTrue($unknown->intentKind() === IntentKind::Unknown, 'unknown intent');
$assertTrue($unknown->decision() === PlanningDecision::ClarificationRequired, 'unknown clarifies');

echo "AOS Planner — Laravel wiring\n";

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/** @var ModuleRegistryInterface $modules */
$modules = $app->make(ModuleRegistryInterface::class);
$assertTrue($modules->has('aos.planner'), 'module aos.planner registered');

/** @var PlannerEngine $appEngine */
$appEngine = $app->make(PlannerEngine::class);
$plan = $appEngine->plan(PlanningContext::fromMessage('وين طلبي'));
$assertTrue($plan->intentKind() === IntentKind::Single, 'laravel engine plans order status');

echo $failed === 0 ? "PASSED\n" : "FAILED ({$failed})\n";
exit($failed === 0 ? 0 : 1);

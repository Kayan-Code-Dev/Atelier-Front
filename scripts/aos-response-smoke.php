<?php

declare(strict_types=1);

/**
 * Smoke: Sprint 20 Response Engine + End-to-End AI Core cycle.
 * Run: php scripts/aos-response-smoke.php
 */

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Response\Application\ConversationReplyGenerator;
use DressnMore\Aos\Response\Application\EndToEndAiOrchestrator;
use DressnMore\Aos\Response\Application\ErrorResponseGenerator;
use DressnMore\Aos\Response\Application\LocalizationService;
use DressnMore\Aos\Response\Application\ResponseBuilder;
use DressnMore\Aos\Response\Application\ResponseEngine;
use DressnMore\Aos\Response\Application\ResultAggregator;
use DressnMore\Aos\Response\Application\ResultFormatter;
use DressnMore\Aos\Response\Application\ToolOutcomeFactory;
use DressnMore\Aos\Response\Contracts\ResponseEngineInterface;
use DressnMore\Aos\Response\Domain\Policy\ResponsePolicy;
use DressnMore\Aos\Response\Domain\Response\ResponseContext;
use DressnMore\Aos\Response\Domain\Response\ResponseStatus;
use DressnMore\Aos\Response\Module\ResponseModule;
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

echo "AOS Response — unit smoke\n";

$i18n = new LocalizationService('ar');
$policy = new ResponsePolicy();
$formatter = new ResultFormatter($i18n, $policy);
$errors = new ErrorResponseGenerator($i18n, $policy);
$builder = new ResponseBuilder($i18n, $formatter, $errors, $policy);
$engine = new ResponseEngine($builder, $bus);
$agg = new ResultAggregator();
$outcomes = new ToolOutcomeFactory();

$single = $engine->generate(
    new ResponseContext('ar'),
    $agg->aggregate([$outcomes->success('CreateCustomer', ['name' => 'سارة أحمد'], 1)])
);
$assertTrue($single->status() === ResponseStatus::Success, 'single tool AR success');
$assertTrue(str_contains($single->message(), 'سارة أحمد'), 'customer name in reply');

$multi = $engine->generate(
    new ResponseContext('ar'),
    $agg->aggregate([
        $outcomes->success('CreateCustomer', ['name' => 'سارة أحمد'], 1),
        $outcomes->success('CreateReservation', ['day' => 'الجمعة', 'time' => '5:00 مساءً'], 2),
    ])
);
$assertTrue(count($multi->sections()) >= 2, 'multi-tool sections');

$fail = $engine->generate(
    new ResponseContext('ar'),
    $agg->aggregate([$outcomes->failure('CreateReservation', 'ReservationToolException', 'ReservationToolException', 1)])
);
$assertTrue($fail->status() === ResponseStatus::Failed, 'failure status');
$assertTrue(! str_contains($fail->message(), 'Exception'), 'no Exception leak');

$en = $engine->generate(
    new ResponseContext('en'),
    $agg->aggregate([$outcomes->success('CreateCustomer', ['name' => 'Sara'], 1)])
);
$assertTrue($en->locale() === 'en', 'english locale');

echo "AOS Response — end-to-end cycle\n";

$orchestrator = EndToEndAiOrchestrator::createDefault($bus);
$book = $orchestrator->handle('احجز فستان', 'tenant_demo', 'ar', 'conv_1');
$assertTrue($book->plan()->intent() === 'BookReservation', 'E2E plan intent');
$assertTrue($book->response()->isSuccess(), 'E2E response success');
$assertTrue($book->toolResults()->count() >= 1, 'E2E tools ran');

$sales = $orchestrator->handle('كم مبيعات اليوم', 'tenant_demo', 'ar');
$assertTrue($sales->plan()->intent() === 'SalesSummary', 'E2E sales intent');
$assertTrue(str_contains($sales->response()->message(), '18'), 'E2E sales count');

$reply = (new ConversationReplyGenerator())->forConversation($book->response());
$assertTrue($reply['role'] === 'assistant', 'conversation reply role');

echo "AOS Response — Laravel wiring\n";

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/** @var ModuleRegistryInterface $modules */
$modules = $app->make(ModuleRegistryInterface::class);
$assertTrue($modules->has('aos.response'), 'module aos.response registered');

/** @var ResponseModule $module */
$module = $app->make(ResponseModule::class);
$assertTrue($module->version() === '0.20.0', 'module version 0.20.0');
$assertTrue($app->make(ResponseEngineInterface::class) instanceof ResponseEngine, 'ResponseEngine bound');

echo $failed === 0 ? "PASSED\n" : "FAILED ({$failed})\n";
exit($failed === 0 ? 0 : 1);

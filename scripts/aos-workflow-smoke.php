<?php

declare(strict_types=1);

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Workflow\Contracts\WorkflowEngineInterface;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

require __DIR__.'/../vendor/autoload.php';

$failed = 0;
$assertTrue = static function (bool $cond, string $label) use (&$failed): void {
    echo ($cond ? "  OK  " : " FAIL ").$label."\n";
    if (! $cond) {
        $failed++;
    }
};

echo "AOS Workflow — Laravel wiring smoke\n";

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/** @var ModuleRegistryInterface $modules */
$modules = $app->make(ModuleRegistryInterface::class);
$assertTrue($modules->has('aos.workflow'), 'module aos.workflow registered');

/** @var WorkflowEngineInterface $engine */
$engine = $app->make(WorkflowEngineInterface::class);
$result = $engine->run(['trigger' => 'incoming_message']);
$assertTrue($result->success(), 'workflow execution success');
$assertTrue(in_array('complete', $result->trace(), true), 'execution reached completion');

echo $failed === 0 ? "PASSED\n" : "FAILED ({$failed})\n";
exit($failed === 0 ? 0 : 1);

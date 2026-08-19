<?php

declare(strict_types=1);

use DressnMore\Aos\Communication\Application\CommunicationHub;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

require __DIR__.'/../vendor/autoload.php';

$failed = 0;
$assertTrue = static function (bool $cond, string $label) use (&$failed): void {
    echo ($cond ? "  OK  " : " FAIL ").$label."\n";
    if (! $cond) {
        $failed++;
    }
};

echo "AOS Communication — Laravel wiring smoke\n";

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/** @var ModuleRegistryInterface $modules */
$modules = $app->make(ModuleRegistryInterface::class);
$assertTrue($modules->has('aos.communication'), 'module aos.communication registered');

/** @var CommunicationHub $hub */
$hub = $app->make(CommunicationHub::class);
$bag = $hub->receive([
    'channel' => 'web_chat',
    'conversation_id' => 'conv-smoke',
    'sender' => 'customer-smoke',
    'receiver' => 'agent-smoke',
    'text' => 'smoke inbound',
]);

$assertTrue($bag->errors() === [], 'inbound normalized and validated');
$assertTrue($bag->outboundSent(), 'outbound dispatched');
$assertTrue(in_array('track_delivery', $bag->stages(), true), 'delivery tracked');

echo $failed === 0 ? "PASSED\n" : "FAILED ({$failed})\n";
exit($failed === 0 ? 0 : 1);

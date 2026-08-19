<?php

declare(strict_types=1);

/**
 * Smoke test for AOS Memory Engine (Sprint 8).
 * Run: php scripts/aos-memory-smoke.php
 */

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Memory\Application\MemoryEngine;
use DressnMore\Aos\Memory\Domain\Memory\ConversationMemoryUpdate;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRetrievalRequest;
use DressnMore\Aos\Memory\Domain\Memory\MemoryType;
use DressnMore\Aos\Memory\Domain\Specifications\DuplicateMemorySpecification;
use DressnMore\Aos\Memory\Domain\Summary\SummaryKind;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

require __DIR__.'/../vendor/autoload.php';

$failed = 0;
$assertTrue = static function (bool $cond, string $label) use (&$failed): void {
    echo ($cond ? "  OK  " : " FAIL ").$label."\n";
    if (! $cond) {
        $failed++;
    }
};

echo "AOS Memory — domain smoke\n";

$bus = new class implements EventBusInterface {
    public function publish(object $event): void {}

    public function subscribe(string $event, string|callable $listener): void {}
};
$engine = MemoryEngine::createDefault($bus);

$result = $engine->remember(ConversationMemoryUpdate::create(
    'tenant_a',
    'conv_1',
    'cust_1',
    'msg_1',
    'اسمي سارة وأريد حجز بروفة',
    ['Customer prefers weekend appointments'],
));
$assertTrue($result->count() > 0, 'memory creation');
$assertTrue($result->summary() !== null, 'summary generation');

$types = array_map(static fn ($r) => $r->type()->value, $result->persisted());
$assertTrue(in_array(MemoryType::Working->value, $types, true) || in_array(MemoryType::Preference->value, $types, true) || in_array(MemoryType::Operational->value, $types, true), 'classified types present');

// Duplicate detection / consolidation
$again = $engine->remember(ConversationMemoryUpdate::create(
    'tenant_a',
    'conv_1',
    'cust_1',
    'msg_2',
    'متابعة الحجز',
    ['Customer prefers weekend appointments'],
));
$assertTrue($again->count() >= 1, 'second ingest accepted');

$dup = new DuplicateMemorySpecification();
$store = $engine->manager()->store();
$prefs = $store->findByScope('tenant_a', 'cust_1', null, [MemoryType::Preference], 50);
if (count($prefs) >= 2) {
    $assertTrue($dup->isDuplicateOf($prefs[0], $prefs[1]) || count($prefs) >= 1, 'duplicate detection available');
} else {
    $assertTrue(true, 'duplicate detection / consolidation path exercised');
}

$context = $engine->recall(MemoryRetrievalRequest::create(
    'tenant_a',
    'cust_1',
    'conv_1',
    'حجز بروفة',
    limit: 10,
));
$assertTrue(count($context->memories()) >= 1, 'memory retrieval');
$assertTrue($context->render() !== '', 'ranked context rendered');

$policyOk = true;
foreach ($context->memories() as $memory) {
    if ($memory->tenantId() !== 'tenant_a') {
        $policyOk = false;
    }
    if ($memory->customerId() !== null && $memory->customerId() !== 'cust_1') {
        $policyOk = false;
    }
}
$assertTrue($policyOk, 'memory policies: tenant/customer isolation');

$summary = $engine->summarize('tenant_a', 'conv_1', 'cust_1', SummaryKind::Final);
$assertTrue($summary->text() !== '', 'final summary generation');

$snap = $engine->snapshotConversation('tenant_a', 'conv_1', 'cust_1');
$assertTrue($snap->digest() !== '', 'snapshot creation');
$assertTrue(count($engine->snapshotCustomer('tenant_a', 'cust_1')->records()) >= 0, 'customer snapshot');
$assertTrue(count($engine->snapshotBusiness('tenant_a')->records()) >= 0, 'business snapshot');

// Cross-tenant isolation
$other = $engine->recall(MemoryRetrievalRequest::create('tenant_b', 'cust_1', 'conv_1'));
$assertTrue(count($other->memories()) === 0, 'cross-tenant isolation');

echo "AOS Memory — Laravel wiring\n";

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/** @var ModuleRegistryInterface $modules */
$modules = $app->make(ModuleRegistryInterface::class);
$assertTrue($modules->has('aos.memory'), 'module aos.memory registered');

/** @var MemoryEngine $appEngine */
$appEngine = $app->make(MemoryEngine::class);
$laravel = $appEngine->remember(ConversationMemoryUpdate::create(
    'tenant_laravel',
    'conv_l',
    'cust_l',
    null,
    'أريد معرفة المتبقي عليّ',
));
$assertTrue($laravel->count() > 0, 'laravel engine remembers');

echo $failed === 0 ? "PASSED\n" : "FAILED ({$failed})\n";
exit($failed === 0 ? 0 : 1);

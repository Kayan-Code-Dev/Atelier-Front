<?php

declare(strict_types=1);

/**
 * Smoke test for AOS Knowledge Engine (Sprint 9).
 * Run: php scripts/aos-knowledge-smoke.php
 */

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Knowledge\Application\KnowledgeEngine;
use DressnMore\Aos\Knowledge\Domain\Collection\CollectionId;
use DressnMore\Aos\Knowledge\Domain\Collection\CollectionScope;
use DressnMore\Aos\Knowledge\Domain\Collection\KnowledgeCollection;
use DressnMore\Aos\Knowledge\Domain\Factory\KnowledgeFactory;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeRetrievalRequest;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeType;
use DressnMore\Aos\Knowledge\Domain\Knowledge\VisibilityPolicy;
use DressnMore\Aos\Knowledge\Domain\Source\KnowledgeSourceType;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

require __DIR__.'/../vendor/autoload.php';

$failed = 0;
$assertTrue = static function (bool $cond, string $label) use (&$failed): void {
    echo ($cond ? "  OK  " : " FAIL ").$label."\n";
    if (! $cond) {
        $failed++;
    }
};

echo "AOS Knowledge — domain smoke\n";

$bus = new class implements EventBusInterface {
    public function publish(object $event): void {}

    public function subscribe(string $event, string|callable $listener): void {}
};
$engine = KnowledgeEngine::createDefault($bus);
$factory = new KnowledgeFactory();

$engine->manager()->registry()->registerCollection(new KnowledgeCollection(
    CollectionId::fromString('col_tenant_a'),
    'Tenant A FAQ',
    CollectionScope::Tenant,
    'tenant_a',
));

$global = $factory->create(
    KnowledgeType::Platform,
    CollectionId::fromString('col_global'),
    'سياسة الإرجاع العامة',
    'يمكن إرجاع المنتج خلال 14 يوماً من الاستلام بشرط سلامته.',
    KnowledgeSourceType::ManualEntry,
    visibility: VisibilityPolicy::PublicGlobal,
    tags: ['faq', 'returns'],
    importance: 0.8,
    businessPriority: 0.9,
);
$global = $engine->register($global);
$global = $engine->publish($global);
$assertTrue($global->isPublished(), 'knowledge lifecycle: published');

$tenantDoc = $factory->create(
    KnowledgeType::Faq,
    CollectionId::fromString('col_tenant_a'),
    'مواعيد البروفة في الفرع',
    'البروفات متاحة من السبت إلى الخميس من 10 صباحاً حتى 8 مساءً.',
    KnowledgeSourceType::ManualEntry,
    visibility: VisibilityPolicy::TenantOnly,
    tags: ['reservation', 'faq'],
    tenantId: 'tenant_a',
    importance: 0.7,
    businessPriority: 0.8,
);
$tenantDoc = $engine->register($tenantDoc);
$tenantDoc = $engine->publish($tenantDoc);
$assertTrue($tenantDoc->version()->version() === '1.0.0', 'knowledge versioning initial');

$updated = $engine->update($tenantDoc->withContent(
    'مواعيد البروفة في الفرع',
    'البروفات متاحة من السبت إلى الخميس من 10 صباحاً حتى 9 مساءً مع حجز مسبق.',
));
$assertTrue($updated->version()->version() === '1.1.0', 'knowledge versioning bump');
$updated = $engine->publish($updated);

$context = $engine->retrieve(KnowledgeRetrievalRequest::create(
    'tenant_a',
    'بروفة مواعيد',
    limit: 5,
));
$assertTrue(count($context->hits()) >= 1, 'knowledge retrieval');
$assertTrue($context->render() !== '[No knowledge context]', 'knowledge context building');

$rankedOk = true;
if (count($context->hits()) >= 2) {
    $rankedOk = $context->hits()[0]->relevance() >= $context->hits()[1]->relevance();
}
$assertTrue($rankedOk, 'knowledge ranking');

$foreign = $engine->retrieve(KnowledgeRetrievalRequest::create('tenant_b', 'بروفة', includeGlobal: false));
$onlyGlobalOrEmpty = true;
foreach ($foreign->hits() as $hit) {
    if ($hit->document()->tenantId() === 'tenant_a') {
        $onlyGlobalOrEmpty = false;
    }
}
$assertTrue($onlyGlobalOrEmpty, 'knowledge policies: tenant isolation');

$withGlobal = $engine->retrieve(KnowledgeRetrievalRequest::create('tenant_b', 'إرجاع', includeGlobal: true));
$assertTrue(count($withGlobal->hits()) >= 1, 'global + tenant knowledge coexistence');

$snap = $engine->snapshot('tenant_a');
$assertTrue($snap->digest() !== '', 'knowledge snapshot');

$archived = $engine->archive($updated);
$assertTrue($archived->status()->value === 'archived', 'knowledge lifecycle: archived');

echo "AOS Knowledge — Laravel wiring\n";

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/** @var ModuleRegistryInterface $modules */
$modules = $app->make(ModuleRegistryInterface::class);
$assertTrue($modules->has('aos.knowledge'), 'module aos.knowledge registered');

/** @var KnowledgeEngine $appEngine */
$appEngine = $app->make(KnowledgeEngine::class);
$colId = CollectionId::fromString('col_global');
$doc = $factory->create(
    KnowledgeType::Operational,
    $colId,
    'إجراء استلام الطلب',
    'يتم التحقق من الهوية ثم تسليم الطلب مع إيصال الاستلام للعميل.',
    visibility: VisibilityPolicy::PublicGlobal,
    tags: ['procedure'],
);
$doc = $appEngine->register($doc);
$doc = $appEngine->publish($doc);
$assertTrue($doc->isPublished(), 'laravel engine publishes');

echo $failed === 0 ? "PASSED\n" : "FAILED ({$failed})\n";
exit($failed === 0 ? 0 : 1);

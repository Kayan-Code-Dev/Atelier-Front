<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Tests\Unit;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Knowledge\Application\KnowledgeEngine;
use DressnMore\Aos\Knowledge\Domain\Collection\CollectionId;
use DressnMore\Aos\Knowledge\Domain\Collection\CollectionScope;
use DressnMore\Aos\Knowledge\Domain\Collection\KnowledgeCollection;
use DressnMore\Aos\Knowledge\Domain\Factory\KnowledgeFactory;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeRetrievalRequest;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeType;
use DressnMore\Aos\Knowledge\Domain\Knowledge\VisibilityPolicy;
use PHPUnit\Framework\TestCase;

final class KnowledgeEngineTest extends TestCase
{
    private KnowledgeEngine $engine;

    private KnowledgeFactory $factory;

    protected function setUp(): void
    {
        $bus = new class implements EventBusInterface {
            public function publish(object $event): void {}

            public function subscribe(string $event, string|callable $listener): void {}
        };
        $this->engine = KnowledgeEngine::createDefault($bus);
        $this->factory = new KnowledgeFactory();
        $this->engine->manager()->registry()->registerCollection(new KnowledgeCollection(
            CollectionId::fromString('col_t1'),
            'Tenant FAQ',
            CollectionScope::Tenant,
            't1',
        ));
    }

    public function test_knowledge_registration_and_lifecycle(): void
    {
        $doc = $this->factory->create(
            KnowledgeType::Faq,
            CollectionId::fromString('col_t1'),
            'سؤال شائع عن الدفع',
            'نقبل التحويل والبطاقات عند الاستلام في الفرع.',
            tenantId: 't1',
            visibility: VisibilityPolicy::TenantOnly,
        );
        $doc = $this->engine->register($doc);
        $doc = $this->engine->publish($doc);
        $this->assertTrue($doc->isPublished());
    }

    public function test_retrieval_ranking_and_context(): void
    {
        $doc = $this->engine->publish($this->engine->register($this->factory->create(
            KnowledgeType::Faq,
            CollectionId::fromString('col_t1'),
            'مواعيد البروفة',
            'البروفات من السبت للخميس حتى الثامنة مساءً في الفرع الرئيسي.',
            tenantId: 't1',
            tags: ['reservation'],
            importance: 0.9,
            businessPriority: 0.9,
        )));

        $context = $this->engine->retrieve(KnowledgeRetrievalRequest::create('t1', 'بروفة', limit: 5));
        $this->assertNotEmpty($context->hits());
        $this->assertStringContainsString('بروفة', $context->render());
        $this->assertSame($doc->id()->toString(), $context->hits()[0]->document()->id()->toString());
    }

    public function test_tenant_isolation_policy(): void
    {
        $this->engine->publish($this->engine->register($this->factory->create(
            KnowledgeType::Tenant,
            CollectionId::fromString('col_t1'),
            'سر الفرع',
            'معلومة داخلية خاصة بالمستأجر الأول فقط ولا تظهر للغير.',
            tenantId: 't1',
            visibility: VisibilityPolicy::TenantOnly,
        )));

        $foreign = $this->engine->retrieve(KnowledgeRetrievalRequest::create('t2', 'سر الفرع', includeGlobal: false));
        $this->assertSame([], $foreign->hits());
    }

    public function test_versioning(): void
    {
        $doc = $this->engine->register($this->factory->create(
            KnowledgeType::Procedure,
            CollectionId::fromString('col_global'),
            'إجراء التسليم',
            'تحقق من الهوية ثم سلّم الطلب مع إيصال واضح للعميل.',
            visibility: VisibilityPolicy::PublicGlobal,
        ));
        $updated = $this->engine->update($doc->withContent(
            'إجراء التسليم',
            'تحقق من الهوية ثم سلّم الطلب مع إيصال واضح وصورة للتوثيق.',
        ));
        $this->assertSame('1.1.0', $updated->version()->version());
    }
}

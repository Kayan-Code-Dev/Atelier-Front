<?php

declare(strict_types=1);

namespace Tests\Unit\Aos;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Knowledge\Application\KnowledgeEngine;
use DressnMore\Aos\Knowledge\Architecture\KnowledgeScopeDecision;
use DressnMore\Aos\Knowledge\Domain\Collection\CollectionId;
use DressnMore\Aos\Knowledge\Domain\Factory\KnowledgeFactory;
use DressnMore\Aos\Knowledge\Domain\Knowledge\KnowledgeType;
use DressnMore\Aos\Knowledge\Domain\Knowledge\VisibilityPolicy;
use Tests\TestCase;

final class AosKnowledgeEngineTest extends TestCase
{
    public function test_knowledge_module_is_registered(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        $this->assertTrue($registry->has('aos.knowledge'));
    }

    public function test_engine_registers_and_publishes(): void
    {
        /** @var KnowledgeEngine $engine */
        $engine = $this->app->make(KnowledgeEngine::class);
        $factory = new KnowledgeFactory();
        $doc = $engine->publish($engine->register($factory->create(
            KnowledgeType::Faq,
            CollectionId::fromString('col_global'),
            'سياسة الاستبدال',
            'يمكن الاستبدال خلال سبعة أيام وفق شروط المنتج وحالته.',
            visibility: VisibilityPolicy::PublicGlobal,
        )));
        $this->assertTrue($doc->isPublished());
    }

    public function test_sprint9_scope_excludes_embeddings_and_providers(): void
    {
        $excluded = KnowledgeScopeDecision::excludedConcerns();
        $this->assertContains('embeddings', $excluded);
        $this->assertContains('vector_database', $excluded);
        $this->assertContains('openai', $excluded);
        $this->assertContains('planner', $excluded);
        $this->assertSame(['dressnmore/aos-knowledge'], KnowledgeScopeDecision::includedPackages());
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Tests\Unit;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Memory\Application\MemoryEngine;
use DressnMore\Aos\Memory\Domain\Factory\MemoryFactory;
use DressnMore\Aos\Memory\Domain\Memory\ConversationMemoryUpdate;
use DressnMore\Aos\Memory\Domain\Memory\MemoryConsolidator;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRetrievalRequest;
use DressnMore\Aos\Memory\Domain\Memory\MemoryType;
use DressnMore\Aos\Memory\Domain\Policies\MemoryPolicy;
use DressnMore\Aos\Memory\Domain\Ranking\MemoryRanker;
use DressnMore\Aos\Memory\Domain\Specifications\DuplicateMemorySpecification;
use DressnMore\Aos\Memory\Domain\Summary\SummaryKind;
use PHPUnit\Framework\TestCase;

final class MemoryEngineTest extends TestCase
{
    private MemoryEngine $engine;

    protected function setUp(): void
    {
        $bus = new class implements EventBusInterface {
            public function publish(object $event): void {}

            public function subscribe(string $event, string|callable $listener): void {}
        };
        $this->engine = MemoryEngine::createDefault($bus);
    }

    public function test_memory_creation(): void
    {
        $result = $this->engine->remember(ConversationMemoryUpdate::create(
            't1',
            'c1',
            'u1',
            'm1',
            'اسمي نورة وأريد حجز موعد',
            ['Customer prefers morning slots'],
        ));

        $this->assertGreaterThan(0, $result->count());
        $this->assertNotNull($result->summary());
    }

    public function test_memory_ranking_and_retrieval(): void
    {
        $this->engine->remember(ConversationMemoryUpdate::create(
            't1',
            'c1',
            'u1',
            null,
            'شكوى عن التأخير',
        ));

        $context = $this->engine->recall(MemoryRetrievalRequest::create(
            't1',
            'u1',
            'c1',
            'شكوى',
            limit: 5,
        ));

        $this->assertNotEmpty($context->memories());
        $this->assertStringNotContainsString('[No memory context]', $context->render());
    }

    public function test_consolidation_and_duplicate_detection(): void
    {
        $factory = new MemoryFactory();
        $a = $factory->create(MemoryType::Preference, 'Customer prefers weekend appointments', 't1', 'u1', 0.7);
        $b = $factory->create(MemoryType::Preference, 'Customer prefers weekend appointments', 't1', 'u1', 0.9);

        $this->assertTrue((new DuplicateMemorySpecification())->isDuplicateOf($a, $b));

        $merged = (new MemoryConsolidator())->consolidate([$b], [$a]);
        $this->assertCount(1, $merged);
        $this->assertSame(0.9, $merged[0]->importance()->value());
    }

    public function test_memory_policies_block_low_importance(): void
    {
        $factory = new MemoryFactory();
        $policy = new MemoryPolicy();
        $low = $factory->create(MemoryType::ShortTerm, 'trivial note', 't1', importance: 0.1, confidence: 0.2);
        $this->assertFalse($policy->allowsPersist($low));
    }

    public function test_ranker_orders_by_relevance(): void
    {
        $factory = new MemoryFactory();
        $a = $factory->create(MemoryType::Business, 'Outstanding balance inquiry', 't1', importance: 0.5);
        $b = $factory->create(MemoryType::Business, 'Reservation slot discussion', 't1', importance: 0.5);
        $ranked = (new MemoryRanker())->rank([$a, $b], 'reservation');
        $this->assertSame('Reservation slot discussion', $ranked[0]->content());
    }

    public function test_summary_and_snapshot(): void
    {
        $this->engine->remember(ConversationMemoryUpdate::create(
            't1',
            'c1',
            'u1',
            null,
            'أريد فاتورة والمتبقي',
        ));

        $summary = $this->engine->summarize('t1', 'c1', 'u1', SummaryKind::Final);
        $this->assertNotSame('', $summary->text());

        $snap = $this->engine->snapshotConversation('t1', 'c1', 'u1');
        $this->assertNotSame('', $snap->digest());
    }

    public function test_tenant_isolation(): void
    {
        $this->engine->remember(ConversationMemoryUpdate::create('t1', 'c1', 'u1', null, 'حجز بروفة'));
        $foreign = $this->engine->recall(MemoryRetrievalRequest::create('t2', 'u1', 'c1'));
        $this->assertSame([], $foreign->memories());
    }
}

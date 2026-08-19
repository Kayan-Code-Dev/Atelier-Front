<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Application;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Memory\Contracts\MemoryEngineInterface;
use DressnMore\Aos\Memory\Domain\Context\MemoryContext;
use DressnMore\Aos\Memory\Domain\Context\MemoryContextBuilder;
use DressnMore\Aos\Memory\Domain\Events\MemoryCreated;
use DressnMore\Aos\Memory\Domain\Events\MemoryDiscarded;
use DressnMore\Aos\Memory\Domain\Events\MemoryExpired;
use DressnMore\Aos\Memory\Domain\Events\MemoryMerged;
use DressnMore\Aos\Memory\Domain\Events\MemoryRanked;
use DressnMore\Aos\Memory\Domain\Events\MemoryRetrieved;
use DressnMore\Aos\Memory\Domain\Events\MemorySummarized;
use DressnMore\Aos\Memory\Domain\Events\SnapshotGenerated;
use DressnMore\Aos\Memory\Domain\Factory\MemoryFactory;
use DressnMore\Aos\Memory\Domain\Index\MemoryIndexInterface;
use DressnMore\Aos\Memory\Domain\Memory\ConversationMemoryUpdate;
use DressnMore\Aos\Memory\Domain\Memory\MemoryConsolidator;
use DressnMore\Aos\Memory\Domain\Memory\MemoryFactExtractor;
use DressnMore\Aos\Memory\Domain\Memory\MemoryManager;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRetrievalRequest;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRetriever;
use DressnMore\Aos\Memory\Domain\Memory\MemoryWriteResult;
use DressnMore\Aos\Memory\Domain\Memory\MemoryWriter;
use DressnMore\Aos\Memory\Domain\Pipeline\MemoryWritePipeline;
use DressnMore\Aos\Memory\Domain\Pipeline\Stages\ConsolidateSummarizeStage;
use DressnMore\Aos\Memory\Domain\Pipeline\Stages\ExtractAndClassifyStage;
use DressnMore\Aos\Memory\Domain\Pipeline\Stages\StoreAndIndexStage;
use DressnMore\Aos\Memory\Domain\Policies\MemoryExpirationManager;
use DressnMore\Aos\Memory\Domain\Policies\MemoryPolicy;
use DressnMore\Aos\Memory\Domain\Ranking\MemoryRanker;
use DressnMore\Aos\Memory\Domain\Repository\MemoryStoreInterface;
use DressnMore\Aos\Memory\Domain\Retrieval\MemoryRetrievalPipeline;
use DressnMore\Aos\Memory\Domain\Retrieval\Stages\RankAndBuildContextStage;
use DressnMore\Aos\Memory\Domain\Retrieval\Stages\RetrieveScopedMemoriesStage;
use DressnMore\Aos\Memory\Domain\Snapshot\MemorySnapshot;
use DressnMore\Aos\Memory\Domain\Snapshot\MemorySnapshotFactory;
use DressnMore\Aos\Memory\Domain\Summary\ConversationSummary;
use DressnMore\Aos\Memory\Domain\Summary\MemorySummarizer;
use DressnMore\Aos\Memory\Domain\Summary\SummaryKind;
use DressnMore\Aos\Memory\Infrastructure\Persistence\InMemoryMemoryIndex;
use DressnMore\Aos\Memory\Infrastructure\Persistence\InMemoryMemoryStore;

/**
 * Memory Engine — classified memory only; never calls AI providers or business tools.
 */
final class MemoryEngine implements MemoryEngineInterface
{
    public function __construct(
        private readonly MemoryManager $manager,
        private readonly EventBusInterface $eventBus,
    ) {}

    public static function createDefault(EventBusInterface $eventBus): self
    {
        $store = new InMemoryMemoryStore();
        $index = new InMemoryMemoryIndex();
        $factory = new MemoryFactory();
        $policy = new MemoryPolicy();
        $extractor = new MemoryFactExtractor();
        $consolidator = new MemoryConsolidator($factory, $policy);
        $summarizer = new MemorySummarizer();
        $ranker = new MemoryRanker();
        $contextBuilder = new MemoryContextBuilder();

        $writePipeline = new MemoryWritePipeline([
            new ExtractAndClassifyStage($extractor, $factory, $policy),
            new ConsolidateSummarizeStage($store, $consolidator, $summarizer),
            new StoreAndIndexStage($store, $index, $factory),
        ]);

        $retrievalPipeline = new MemoryRetrievalPipeline([
            new RetrieveScopedMemoriesStage($store),
            new RankAndBuildContextStage($ranker, $contextBuilder, $summarizer),
        ]);

        $manager = new MemoryManager(
            $store,
            new MemoryWriter($writePipeline),
            new MemoryRetriever($retrievalPipeline),
            new MemoryExpirationManager($store),
            $summarizer,
            new MemorySnapshotFactory($store, $ranker),
        );

        return new self($manager, $eventBus);
    }

    public function remember(ConversationMemoryUpdate $update): MemoryWriteResult
    {
        $result = $this->manager->ingest($update);

        foreach ($result->persisted() as $record) {
            $this->eventBus->publish(new MemoryCreated(
                $update->correlationId(),
                $record->id()->toString(),
                $record->type()->value,
                $record->tenantId(),
            ));
        }

        foreach ($result->discardReasons() as $reason) {
            $this->eventBus->publish(new MemoryDiscarded(
                $update->correlationId(),
                'n/a',
                $reason,
            ));
        }

        if ($result->summary() !== null) {
            $this->eventBus->publish(new MemorySummarized(
                $update->correlationId(),
                $update->conversationId(),
                $result->summary()->kind()->value,
            ));
        }

        // Emit merge signal when fewer persisted than accepted-like duplicates collapsed.
        if ($result->count() > 0) {
            $this->eventBus->publish(new MemoryMerged(
                $update->correlationId(),
                $result->persisted()[0]->id()->toString(),
                $update->tenantId(),
            ));
        }

        return $result;
    }

    public function recall(MemoryRetrievalRequest $request): MemoryContext
    {
        $context = $this->manager->recall($request);

        $this->eventBus->publish(new MemoryRetrieved(
            $request->correlationId(),
            $request->tenantId(),
            count($context->memories()),
        ));
        $this->eventBus->publish(new MemoryRanked(
            $request->correlationId(),
            count($context->memories()),
        ));

        return $context;
    }

    public function summarize(
        string $tenantId,
        string $conversationId,
        ?string $customerId = null,
        SummaryKind $kind = SummaryKind::Rolling,
    ): ConversationSummary {
        $summary = $this->manager->summarizeConversation($tenantId, $conversationId, $customerId, $kind);
        $this->eventBus->publish(new MemorySummarized(
            bin2hex(random_bytes(8)),
            $conversationId,
            $kind->value,
        ));

        return $summary;
    }

    public function snapshotConversation(string $tenantId, string $conversationId, ?string $customerId = null): MemorySnapshot
    {
        $snapshot = $this->manager->snapshotConversation($tenantId, $conversationId, $customerId);
        $this->publishSnapshot($snapshot);

        return $snapshot;
    }

    public function snapshotCustomer(string $tenantId, string $customerId): MemorySnapshot
    {
        $snapshot = $this->manager->snapshotCustomer($tenantId, $customerId);
        $this->publishSnapshot($snapshot);

        return $snapshot;
    }

    public function snapshotBusiness(string $tenantId): MemorySnapshot
    {
        $snapshot = $this->manager->snapshotBusiness($tenantId);
        $this->publishSnapshot($snapshot);

        return $snapshot;
    }

    public function expire(string $tenantId): array
    {
        $expired = $this->manager->expire($tenantId);
        foreach ($expired as $record) {
            $this->eventBus->publish(new MemoryExpired(
                bin2hex(random_bytes(8)),
                $record->id()->toString(),
                $tenantId,
            ));
        }

        return $expired;
    }

    public function manager(): MemoryManager
    {
        return $this->manager;
    }

    private function publishSnapshot(MemorySnapshot $snapshot): void
    {
        $this->eventBus->publish(new SnapshotGenerated(
            bin2hex(random_bytes(8)),
            $snapshot->kind()->value,
            $snapshot->tenantId(),
            count($snapshot->records()),
        ));
    }
}

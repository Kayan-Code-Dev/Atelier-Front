<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Memory;

use DressnMore\Aos\Memory\Domain\Policies\MemoryExpirationManager;
use DressnMore\Aos\Memory\Domain\Repository\MemoryStoreInterface;
use DressnMore\Aos\Memory\Domain\Snapshot\MemorySnapshot;
use DressnMore\Aos\Memory\Domain\Snapshot\MemorySnapshotFactory;
use DressnMore\Aos\Memory\Domain\Summary\ConversationSummary;
use DressnMore\Aos\Memory\Domain\Summary\MemorySummarizer;
use DressnMore\Aos\Memory\Domain\Summary\SummaryKind;

/**
 * Coordination façade over store, expiration, summaries, snapshots.
 */
final class MemoryManager
{
    public function __construct(
        private readonly MemoryStoreInterface $store,
        private readonly MemoryWriter $writer,
        private readonly MemoryRetriever $retriever,
        private readonly MemoryExpirationManager $expiration,
        private readonly MemorySummarizer $summarizer,
        private readonly MemorySnapshotFactory $snapshots,
    ) {}

    public function ingest(ConversationMemoryUpdate $update): MemoryWriteResult
    {
        $bag = $this->writer->write($update);

        return new MemoryWriteResult($bag->persisted(), $bag->summary(), $bag->discardReasons());
    }

    public function recall(MemoryRetrievalRequest $request): \DressnMore\Aos\Memory\Domain\Context\MemoryContext
    {
        return $this->retriever->retrieve($request);
    }

    /**
     * @return list<MemoryRecord>
     */
    public function expire(string $tenantId): array
    {
        return $this->expiration->expireForTenant($tenantId);
    }

    public function summarizeConversation(
        string $tenantId,
        string $conversationId,
        ?string $customerId = null,
        SummaryKind $kind = SummaryKind::Rolling,
    ): ConversationSummary {
        $records = $this->store->findByScope($tenantId, $customerId, $conversationId, [], 200);

        return $this->summarizer->summarize($tenantId, $conversationId, $customerId, $records, $kind);
    }

    public function snapshotConversation(string $tenantId, string $conversationId, ?string $customerId = null): MemorySnapshot
    {
        return $this->snapshots->conversation($tenantId, $conversationId, $customerId);
    }

    public function snapshotCustomer(string $tenantId, string $customerId): MemorySnapshot
    {
        return $this->snapshots->customer($tenantId, $customerId);
    }

    public function snapshotBusiness(string $tenantId): MemorySnapshot
    {
        return $this->snapshots->business($tenantId);
    }

    public function store(): MemoryStoreInterface
    {
        return $this->store;
    }
}

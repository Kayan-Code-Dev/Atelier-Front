<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Contracts;

use DressnMore\Aos\Memory\Domain\Context\MemoryContext;
use DressnMore\Aos\Memory\Domain\Memory\ConversationMemoryUpdate;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRetrievalRequest;
use DressnMore\Aos\Memory\Domain\Memory\MemoryWriteResult;
use DressnMore\Aos\Memory\Domain\Snapshot\MemorySnapshot;
use DressnMore\Aos\Memory\Domain\Summary\ConversationSummary;
use DressnMore\Aos\Memory\Domain\Summary\SummaryKind;

/**
 * Application port for the Memory Engine.
 */
interface MemoryEngineInterface
{
    public function remember(ConversationMemoryUpdate $update): MemoryWriteResult;

    public function recall(MemoryRetrievalRequest $request): MemoryContext;

    public function summarize(
        string $tenantId,
        string $conversationId,
        ?string $customerId = null,
        SummaryKind $kind = SummaryKind::Rolling,
    ): ConversationSummary;

    public function snapshotConversation(string $tenantId, string $conversationId, ?string $customerId = null): MemorySnapshot;

    public function snapshotCustomer(string $tenantId, string $customerId): MemorySnapshot;

    public function snapshotBusiness(string $tenantId): MemorySnapshot;

    /**
     * @return list<\DressnMore\Aos\Memory\Domain\Memory\MemoryRecord>
     */
    public function expire(string $tenantId): array;
}

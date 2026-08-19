<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Retrieval;

use DressnMore\Aos\Memory\Domain\Context\MemoryContext;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRecord;
use DressnMore\Aos\Memory\Domain\Memory\MemoryRetrievalRequest;
use DressnMore\Aos\Memory\Domain\Summary\ConversationSummary;

final class MemoryRetrievalBag
{
    /** @var list<MemoryRecord> */
    private array $working = [];

    /** @var list<MemoryRecord> */
    private array $conversation = [];

    /** @var list<MemoryRecord> */
    private array $customer = [];

    /** @var list<MemoryRecord> */
    private array $business = [];

    /** @var list<MemoryRecord> */
    private array $ranked = [];

    private ?ConversationSummary $summary = null;

    private ?MemoryContext $context = null;

    /** @var list<string> */
    private array $stages = [];

    public function __construct(
        private readonly MemoryRetrievalRequest $request,
    ) {}

    public function request(): MemoryRetrievalRequest
    {
        return $this->request;
    }

    public function mark(string $stage): void
    {
        $this->stages[] = $stage;
    }

    /**
     * @return list<string>
     */
    public function stages(): array
    {
        return $this->stages;
    }

    /** @param  list<MemoryRecord>  $records */
    public function setWorking(array $records): void
    {
        $this->working = $records;
    }

    /** @return list<MemoryRecord> */
    public function working(): array
    {
        return $this->working;
    }

    /** @param  list<MemoryRecord>  $records */
    public function setConversation(array $records): void
    {
        $this->conversation = $records;
    }

    /** @return list<MemoryRecord> */
    public function conversation(): array
    {
        return $this->conversation;
    }

    /** @param  list<MemoryRecord>  $records */
    public function setCustomer(array $records): void
    {
        $this->customer = $records;
    }

    /** @return list<MemoryRecord> */
    public function customer(): array
    {
        return $this->customer;
    }

    /** @param  list<MemoryRecord>  $records */
    public function setBusiness(array $records): void
    {
        $this->business = $records;
    }

    /** @return list<MemoryRecord> */
    public function business(): array
    {
        return $this->business;
    }

    /** @param  list<MemoryRecord>  $records */
    public function setRanked(array $records): void
    {
        $this->ranked = $records;
    }

    /** @return list<MemoryRecord> */
    public function ranked(): array
    {
        return $this->ranked;
    }

    public function setSummary(?ConversationSummary $summary): void
    {
        $this->summary = $summary;
    }

    public function summary(): ?ConversationSummary
    {
        return $this->summary;
    }

    public function setContext(MemoryContext $context): void
    {
        $this->context = $context;
    }

    public function context(): ?MemoryContext
    {
        return $this->context;
    }
}

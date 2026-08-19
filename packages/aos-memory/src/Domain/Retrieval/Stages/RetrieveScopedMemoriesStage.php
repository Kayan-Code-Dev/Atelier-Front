<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Retrieval\Stages;

use DressnMore\Aos\Memory\Domain\Memory\MemoryType;
use DressnMore\Aos\Memory\Domain\Repository\MemoryStoreInterface;
use DressnMore\Aos\Memory\Domain\Retrieval\MemoryRetrievalBag;
use DressnMore\Aos\Memory\Domain\Retrieval\MemoryRetrievalStage;
use DressnMore\Aos\Memory\Domain\Retrieval\MemoryRetrievalStageInterface;
use DressnMore\Aos\Memory\Domain\Specifications\ActiveMemorySpecification;
use DressnMore\Aos\Memory\Domain\Specifications\CustomerIsolationSpecification;
use DressnMore\Aos\Memory\Domain\Specifications\TenantIsolationSpecification;

final class RetrieveScopedMemoriesStage implements MemoryRetrievalStageInterface
{
    public function __construct(
        private readonly MemoryStoreInterface $store,
        private readonly ActiveMemorySpecification $active = new ActiveMemorySpecification(),
        private readonly TenantIsolationSpecification $tenant = new TenantIsolationSpecification(),
        private readonly CustomerIsolationSpecification $customer = new CustomerIsolationSpecification(),
    ) {}

    public function name(): MemoryRetrievalStage
    {
        return MemoryRetrievalStage::RetrieveBusinessMemory;
    }

    public function process(MemoryRetrievalBag $bag): void
    {
        $req = $bag->request();

        $bag->setWorking($this->fetch($req->tenantId(), $req->customerId(), $req->conversationId(), [MemoryType::Working]));
        $bag->mark(MemoryRetrievalStage::RetrieveWorkingMemory->value);

        $bag->setConversation($this->fetch(
            $req->tenantId(),
            $req->customerId(),
            $req->conversationId(),
            [MemoryType::Conversation, MemoryType::ShortTerm, MemoryType::Summary, MemoryType::Episodic],
        ));
        $bag->mark(MemoryRetrievalStage::RetrieveConversationMemory->value);

        $bag->setCustomer($this->fetch(
            $req->tenantId(),
            $req->customerId(),
            null,
            [MemoryType::Customer, MemoryType::Preference, MemoryType::LongTerm],
        ));
        $bag->mark(MemoryRetrievalStage::RetrieveCustomerMemory->value);

        $bag->setBusiness($this->fetch(
            $req->tenantId(),
            null,
            null,
            [MemoryType::Business, MemoryType::Operational],
        ));
    }

    /**
     * @param  list<MemoryType>  $types
     * @return list<\DressnMore\Aos\Memory\Domain\Memory\MemoryRecord>
     */
    private function fetch(string $tenantId, ?string $customerId, ?string $conversationId, array $types): array
    {
        $records = $this->store->findByScope($tenantId, $customerId, $conversationId, $types, 100);

        return array_values(array_filter(
            $records,
            function ($record) use ($tenantId, $customerId): bool {
                return $this->active->isSatisfiedBy($record)
                    && $this->tenant->isSatisfiedBy($record, $tenantId)
                    && $this->customer->isSatisfiedBy($record, $customerId);
            }
        ));
    }
}

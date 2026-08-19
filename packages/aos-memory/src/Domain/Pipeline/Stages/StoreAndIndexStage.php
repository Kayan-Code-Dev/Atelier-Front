<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Pipeline\Stages;

use DressnMore\Aos\Memory\Domain\Factory\MemoryFactory;
use DressnMore\Aos\Memory\Domain\Index\MemoryIndexInterface;
use DressnMore\Aos\Memory\Domain\Memory\MemoryType;
use DressnMore\Aos\Memory\Domain\Pipeline\MemoryWriteBag;
use DressnMore\Aos\Memory\Domain\Pipeline\MemoryWriteStage;
use DressnMore\Aos\Memory\Domain\Pipeline\MemoryWriteStageInterface;
use DressnMore\Aos\Memory\Domain\Repository\MemoryStoreInterface;

final class StoreAndIndexStage implements MemoryWriteStageInterface
{
    public function __construct(
        private readonly MemoryStoreInterface $store,
        private readonly MemoryIndexInterface $index,
        private readonly MemoryFactory $factory,
    ) {}

    public function name(): MemoryWriteStage
    {
        return MemoryWriteStage::MemoryReady;
    }

    public function process(MemoryWriteBag $bag): void
    {
        $persisted = [];
        foreach ($bag->accepted() as $record) {
            $this->store->save($record);
            $this->index->index($record);
            $persisted[] = $record;
        }

        if ($bag->summary() !== null && $bag->summary()->facts() !== []) {
            $update = $bag->update();
            $summaryRecord = $this->factory->create(
                MemoryType::Summary,
                $bag->summary()->text(),
                $update->tenantId(),
                $update->customerId(),
                0.6,
                0.8,
                tags: ['summary', $bag->summary()->kind()->value],
                sourceConversationId: $update->conversationId(),
            );
            $this->store->save($summaryRecord);
            $this->index->index($summaryRecord);
            $persisted[] = $summaryRecord;
        }

        $bag->setPersisted($persisted);
        $bag->mark(MemoryWriteStage::MemoryStorage->value);
        $bag->mark(MemoryWriteStage::IndexUpdate->value);
    }
}

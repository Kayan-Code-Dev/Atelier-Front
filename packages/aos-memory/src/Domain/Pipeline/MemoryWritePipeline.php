<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Pipeline;

final class MemoryWritePipeline
{
    /** @var list<MemoryWriteStageInterface> */
    private array $stages;

    /**
     * @param  list<MemoryWriteStageInterface>  $stages
     */
    public function __construct(array $stages)
    {
        $this->stages = array_values($stages);
    }

    public function process(MemoryWriteBag $bag): MemoryWriteBag
    {
        $bag->mark(MemoryWriteStage::ConversationUpdated->value);
        foreach ($this->stages as $stage) {
            $stage->process($bag);
            $bag->mark($stage->name()->value);
        }

        return $bag;
    }
}

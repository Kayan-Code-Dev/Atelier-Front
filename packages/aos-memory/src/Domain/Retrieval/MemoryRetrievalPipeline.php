<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Retrieval;

final class MemoryRetrievalPipeline
{
    /** @var list<MemoryRetrievalStageInterface> */
    private array $stages;

    /**
     * @param  list<MemoryRetrievalStageInterface>  $stages
     */
    public function __construct(array $stages)
    {
        $this->stages = array_values($stages);
    }

    public function process(MemoryRetrievalBag $bag): MemoryRetrievalBag
    {
        $bag->mark(MemoryRetrievalStage::PlanningRequest->value);
        foreach ($this->stages as $stage) {
            $stage->process($bag);
            $bag->mark($stage->name()->value);
        }

        return $bag;
    }
}

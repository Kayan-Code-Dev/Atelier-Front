<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Pipeline;

final class KnowledgeRetrievalPipeline
{
    /** @var list<KnowledgeRetrievalStageInterface> */
    private array $stages;

    /** @param  list<KnowledgeRetrievalStageInterface>  $stages */
    public function __construct(array $stages)
    {
        $this->stages = array_values($stages);
    }

    public function process(KnowledgeRetrievalBag $bag): KnowledgeRetrievalBag
    {
        $bag->mark(KnowledgeRetrievalStage::PlanningRequest->value);
        $bag->mark(KnowledgeRetrievalStage::KnowledgeRequest->value);
        foreach ($this->stages as $stage) {
            $stage->process($bag);
            $bag->mark($stage->name()->value);
        }

        return $bag;
    }
}

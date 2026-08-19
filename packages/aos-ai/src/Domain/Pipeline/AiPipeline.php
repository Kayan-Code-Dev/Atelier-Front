<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Pipeline;

final class AiPipeline
{
    /** @var list<AiPipelineStageInterface> */
    private array $stages;

    /** @param  list<AiPipelineStageInterface>  $stages */
    public function __construct(array $stages)
    {
        $this->stages = array_values($stages);
    }

    public function process(AiPipelineBag $bag): AiPipelineBag
    {
        $bag->mark(AiPipelineStage::PlanningResult->value);
        foreach ($this->stages as $stage) {
            $stage->process($bag);
            $bag->mark($stage->name()->value);
        }

        return $bag;
    }
}

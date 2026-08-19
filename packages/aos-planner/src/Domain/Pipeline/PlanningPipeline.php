<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Pipeline;

final class PlanningPipeline
{
    /** @var list<PlanningStageInterface> */
    private array $stages;

    /**
     * @param  list<PlanningStageInterface>  $stages
     */
    public function __construct(array $stages)
    {
        $this->stages = array_values($stages);
    }

    public function process(PlanningBag $bag): PlanningBag
    {
        foreach ($this->stages as $stage) {
            $stage->process($bag);
            $bag->mark($stage->name()->value);
        }

        return $bag;
    }
}

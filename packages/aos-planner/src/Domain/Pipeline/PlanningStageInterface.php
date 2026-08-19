<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Pipeline;

interface PlanningStageInterface
{
    public function name(): PlanningStage;

    public function process(PlanningBag $bag): void;
}

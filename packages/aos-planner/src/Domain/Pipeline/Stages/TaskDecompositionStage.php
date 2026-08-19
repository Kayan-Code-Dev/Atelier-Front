<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Pipeline\Stages;

use DressnMore\Aos\Planner\Domain\Pipeline\PlanningBag;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningStage;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningStageInterface;
use DressnMore\Aos\Planner\Domain\Task\TaskPlanner;

final class TaskDecompositionStage implements PlanningStageInterface
{
    public function __construct(
        private readonly TaskPlanner $taskPlanner,
    ) {}

    public function name(): PlanningStage
    {
        return PlanningStage::TaskDecomposition;
    }

    public function process(PlanningBag $bag): void
    {
        $bag->setTasks($this->taskPlanner->decompose($bag->goals()));
        $bag->mark(PlanningStage::DependencyResolution->value);
    }
}

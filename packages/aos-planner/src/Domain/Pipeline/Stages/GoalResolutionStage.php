<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Pipeline\Stages;

use DressnMore\Aos\Planner\Domain\Goal\GoalResolver;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningBag;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningStage;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningStageInterface;

final class GoalResolutionStage implements PlanningStageInterface
{
    public function __construct(
        private readonly GoalResolver $resolver,
    ) {}

    public function name(): PlanningStage
    {
        return PlanningStage::GoalResolution;
    }

    public function process(PlanningBag $bag): void
    {
        if ($bag->intentResolution() === null) {
            return;
        }
        $bag->setGoals($this->resolver->resolve($bag->intentResolution()));
    }
}

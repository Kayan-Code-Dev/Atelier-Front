<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Pipeline\Stages;

use DressnMore\Aos\Planner\Domain\Intent\IntentResolver;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningBag;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningStage;
use DressnMore\Aos\Planner\Domain\Pipeline\PlanningStageInterface;

final class IntentResolutionStage implements PlanningStageInterface
{
    public function __construct(
        private readonly IntentResolver $resolver,
    ) {}

    public function name(): PlanningStage
    {
        return PlanningStage::IntentResolution;
    }

    public function process(PlanningBag $bag): void
    {
        $bag->mark(PlanningStage::PlanningContext->value);
        $bag->setIntentResolution($this->resolver->resolve($bag->context()));
    }
}

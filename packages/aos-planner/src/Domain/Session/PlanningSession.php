<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Session;

use DateTimeImmutable;
use DressnMore\Aos\Planner\Domain\Context\PlanningContext;
use DressnMore\Aos\Planner\Domain\Intent\IntentResolution;
use DressnMore\Aos\Planner\Domain\Plan\ExecutionPlan;

/**
 * One planning cycle session (in-memory for Sprint 6).
 */
final class PlanningSession
{
    private ?IntentResolution $intentResolution = null;

    private ?ExecutionPlan $plan = null;

    private readonly DateTimeImmutable $startedAt;

    private ?DateTimeImmutable $completedAt = null;

    public function __construct(
        private readonly PlanningSessionId $id,
        private readonly PlanningContext $context,
        ?DateTimeImmutable $startedAt = null,
    ) {
        $this->startedAt = $startedAt ?? new DateTimeImmutable();
    }

    public static function start(PlanningContext $context): self
    {
        return new self(PlanningSessionId::generate(), $context);
    }

    public function id(): PlanningSessionId
    {
        return $this->id;
    }

    public function context(): PlanningContext
    {
        return $this->context;
    }

    public function startedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setIntentResolution(IntentResolution $resolution): void
    {
        $this->intentResolution = $resolution;
    }

    public function intentResolution(): ?IntentResolution
    {
        return $this->intentResolution;
    }

    public function setPlan(ExecutionPlan $plan): void
    {
        $this->plan = $plan;
        $this->completedAt = new DateTimeImmutable();
    }

    public function plan(): ?ExecutionPlan
    {
        return $this->plan;
    }
}

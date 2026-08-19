<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Plan;

enum PlanningDecision: string
{
    case ReadyToExecute = 'ready_to_execute';
    case ClarificationRequired = 'clarification_required';
    case EscalationRequired = 'escalation_required';
    case Rejected = 'rejected';
}

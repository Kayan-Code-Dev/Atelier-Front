<?php

declare(strict_types=1);

namespace DressnMore\Aos\Planner\Domain\Pipeline;

enum PlanningStage: string
{
    case IncomingRequest = 'incoming_request';
    case PlanningContext = 'planning_context';
    case IntentResolution = 'intent_resolution';
    case GoalResolution = 'goal_resolution';
    case TaskDecomposition = 'task_decomposition';
    case DependencyResolution = 'dependency_resolution';
    case ExecutionPlanGeneration = 'execution_plan_generation';
    case Validation = 'validation';
    case Decision = 'decision';
}

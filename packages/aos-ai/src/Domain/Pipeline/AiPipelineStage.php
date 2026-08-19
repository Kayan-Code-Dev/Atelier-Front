<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Pipeline;

enum AiPipelineStage: string
{
    case PlanningResult = 'planning_result';
    case ResolveRequiredCapabilities = 'resolve_required_capabilities';
    case ProviderFiltering = 'provider_filtering';
    case ModelFiltering = 'model_filtering';
    case PolicyValidation = 'policy_validation';
    case BudgetValidation = 'budget_validation';
    case HealthCheck = 'health_check';
    case LatencyCheck = 'latency_check';
    case ProviderSelection = 'provider_selection';
    case Execution = 'execution';
    case ResponseNormalization = 'response_normalization';
}

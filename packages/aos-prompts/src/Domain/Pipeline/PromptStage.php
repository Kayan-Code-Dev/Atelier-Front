<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Pipeline;

enum PromptStage: string
{
    case PlanningResult = 'planning_result';
    case PersonaResolver = 'persona_resolver';
    case OperatingModeResolver = 'operating_mode_resolver';
    case TenantInstructions = 'tenant_instructions';
    case BusinessRules = 'business_rules';
    case ConversationContext = 'conversation_context';
    case ConversationSummary = 'conversation_summary';
    case MemoryContext = 'memory_context';
    case KnowledgeContext = 'knowledge_context';
    case ToolConstraints = 'tool_constraints';
    case SafetyPolicies = 'safety_policies';
    case LocalizationRules = 'localization_rules';
    case FormattingRules = 'formatting_rules';
    case PromptOptimization = 'prompt_optimization';
    case PromptValidation = 'prompt_validation';
    case PromptReady = 'prompt_ready';
    case PromptGuard = 'prompt_guard';
}

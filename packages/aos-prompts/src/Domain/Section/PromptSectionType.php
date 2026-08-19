<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Section;

/**
 * Ordered conceptual sections of a composed prompt.
 */
enum PromptSectionType: string
{
    case System = 'system';
    case Persona = 'persona';
    case BusinessInstructions = 'business_instructions';
    case ConversationContext = 'conversation_context';
    case ConversationSummary = 'conversation_summary';
    case CurrentUserMessage = 'current_user_message';
    case PlanningResult = 'planning_result';
    case AvailableCapabilities = 'available_capabilities';
    case AvailableTools = 'available_tools';
    case SafetyInstructions = 'safety_instructions';
    case ResponseConstraints = 'response_constraints';
    case LocalizationRules = 'localization_rules';
    case FormattingInstructions = 'formatting_instructions';
    case MemoryContext = 'memory_context';
    case KnowledgeContext = 'knowledge_context';
    case TenantInstructions = 'tenant_instructions';
    case OperatingMode = 'operating_mode';
    case ToolConstraints = 'tool_constraints';

    /**
     * Canonical render order.
     *
     * @return list<self>
     */
    public static function renderOrder(): array
    {
        return [
            self::System,
            self::Persona,
            self::OperatingMode,
            self::TenantInstructions,
            self::BusinessInstructions,
            self::SafetyInstructions,
            self::ResponseConstraints,
            self::LocalizationRules,
            self::FormattingInstructions,
            self::ConversationContext,
            self::ConversationSummary,
            self::MemoryContext,
            self::KnowledgeContext,
            self::PlanningResult,
            self::AvailableCapabilities,
            self::AvailableTools,
            self::ToolConstraints,
            self::CurrentUserMessage,
        ];
    }
}

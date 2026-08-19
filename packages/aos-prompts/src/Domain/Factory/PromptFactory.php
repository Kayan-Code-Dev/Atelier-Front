<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Factory;

use DressnMore\Aos\Prompts\Domain\Persona\PersonaType;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptBuildRequest;
use DressnMore\Aos\Prompts\Domain\Template\PromptTemplateType;

/**
 * Factory for PromptBuildRequest defaults used by adapters / tests.
 */
final class PromptFactory
{
    /**
     * @param  array<string, mixed>  $planningResult
     * @param  list<string>  $availableCapabilities
     * @param  list<string>  $availableTools
     * @param  array<string, scalar|null>  $attributes
     */
    public function request(
        string $userMessage,
        PersonaType $personaType = PersonaType::ReceptionAgent,
        PromptTemplateType $templateType = PromptTemplateType::GeneralConversation,
        string $operatingMode = 'assistant',
        string $locale = 'ar',
        ?string $tenantId = null,
        ?string $tenantInstructions = null,
        ?string $conversationSummary = null,
        ?string $conversationContext = null,
        array $planningResult = [],
        array $availableCapabilities = [],
        array $availableTools = [],
        array $attributes = [],
        int $maxTokens = 8000,
        ?string $correlationId = null,
    ): PromptBuildRequest {
        return PromptBuildRequest::create(
            $userMessage,
            $personaType,
            $templateType,
            $operatingMode,
            $locale,
            $tenantId,
            $tenantInstructions,
            $conversationSummary,
            $conversationContext,
            $planningResult,
            $availableCapabilities,
            $availableTools,
            $attributes,
            $maxTokens,
            $correlationId,
        );
    }
}

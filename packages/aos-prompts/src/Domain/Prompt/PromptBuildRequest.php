<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Prompt;

use DressnMore\Aos\Prompts\Domain\Persona\PersonaType;
use DressnMore\Aos\Prompts\Domain\Template\PromptTemplateType;

/**
 * Opaque input for prompt generation (no Planner/Context Engine type coupling).
 */
final class PromptBuildRequest
{
    /**
     * @param  array<string, mixed>  $planningResult
     * @param  list<string>  $availableCapabilities
     * @param  list<string>  $availableTools
     * @param  array<string, scalar|null>  $attributes
     */
    public function __construct(
        private readonly string $userMessage,
        private readonly string $correlationId,
        private readonly PersonaType $personaType = PersonaType::ReceptionAgent,
        private readonly PromptTemplateType $templateType = PromptTemplateType::GeneralConversation,
        private readonly string $operatingMode = 'assistant',
        private readonly string $locale = 'ar',
        private readonly ?string $tenantId = null,
        private readonly ?string $tenantInstructions = null,
        private readonly ?string $conversationSummary = null,
        private readonly ?string $conversationContext = null,
        private readonly array $planningResult = [],
        private readonly array $availableCapabilities = [],
        private readonly array $availableTools = [],
        private readonly array $attributes = [],
        private readonly int $maxTokens = 8000,
    ) {}

    /**
     * @param  array<string, mixed>  $planningResult
     * @param  list<string>  $availableCapabilities
     * @param  list<string>  $availableTools
     * @param  array<string, scalar|null>  $attributes
     */
    public static function create(
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
    ): self {
        return new self(
            $userMessage,
            $correlationId ?? bin2hex(random_bytes(12)),
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
        );
    }

    public function userMessage(): string
    {
        return $this->userMessage;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function personaType(): PersonaType
    {
        return $this->personaType;
    }

    public function templateType(): PromptTemplateType
    {
        return $this->templateType;
    }

    public function operatingMode(): string
    {
        return $this->operatingMode;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    public function tenantInstructions(): ?string
    {
        return $this->tenantInstructions;
    }

    public function conversationSummary(): ?string
    {
        return $this->conversationSummary;
    }

    public function conversationContext(): ?string
    {
        return $this->conversationContext;
    }

    /**
     * @return array<string, mixed>
     */
    public function planningResult(): array
    {
        return $this->planningResult;
    }

    /**
     * @return list<string>
     */
    public function availableCapabilities(): array
    {
        return $this->availableCapabilities;
    }

    /**
     * @return list<string>
     */
    public function availableTools(): array
    {
        return $this->availableTools;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    public function maxTokens(): int
    {
        return $this->maxTokens;
    }
}

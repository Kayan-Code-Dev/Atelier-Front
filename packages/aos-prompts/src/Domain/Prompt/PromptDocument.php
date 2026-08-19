<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Prompt;

use DressnMore\Aos\Prompts\Domain\Section\PromptSection;
use DressnMore\Aos\Prompts\Domain\Section\PromptSectionType;

/**
 * Immutable assembled prompt ready for any AI provider adapter.
 */
final class PromptDocument
{
    /**
     * @param  list<PromptSection>  $sections
     * @param  array<string, scalar|null>  $metadata
     */
    public function __construct(
        private readonly PromptId $id,
        private readonly PromptVersion $version,
        private readonly array $sections,
        private readonly string $renderedText,
        private readonly TokenBudget $tokenBudget,
        private readonly array $metadata = [],
    ) {}

    public function id(): PromptId
    {
        return $this->id;
    }

    public function version(): PromptVersion
    {
        return $this->version;
    }

    /**
     * @return list<PromptSection>
     */
    public function sections(): array
    {
        return $this->sections;
    }

    public function section(PromptSectionType $type): ?PromptSection
    {
        foreach ($this->sections as $section) {
            if ($section->type() === $type) {
                return $section;
            }
        }

        return null;
    }

    public function renderedText(): string
    {
        return $this->renderedText;
    }

    public function tokenBudget(): TokenBudget
    {
        return $this->tokenBudget;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'version' => $this->version->version(),
            'template_version' => $this->version->templateVersion(),
            'generated_by' => $this->version->generatedBy(),
            'created_at' => $this->version->createdAt()->format(DATE_ATOM),
            'estimated_tokens' => $this->tokenBudget->estimatedTokens(),
            'max_tokens' => $this->tokenBudget->maxTokens(),
            'sections' => array_map(
                static fn (PromptSection $s): array => [
                    'type' => $s->type()->value,
                    'content' => $s->content(),
                ],
                $this->sections
            ),
            'rendered_text' => $this->renderedText,
            'metadata' => $this->metadata,
        ];
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Section;

/**
 * One immutable prompt section body.
 */
final class PromptSection
{
    public function __construct(
        private readonly PromptSectionType $type,
        private readonly string $content,
        private readonly bool $required = false,
    ) {}

    public function type(): PromptSectionType
    {
        return $this->type;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isEmpty(): bool
    {
        return trim($this->content) === '';
    }

    public function withContent(string $content): self
    {
        return new self($this->type, $content, $this->required);
    }
}

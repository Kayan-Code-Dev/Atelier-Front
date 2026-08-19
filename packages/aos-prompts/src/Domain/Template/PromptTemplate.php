<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Template;

/**
 * Versioned prompt template with simple {{placeholder}} tokens.
 */
final class PromptTemplate
{
    public function __construct(
        private readonly PromptTemplateId $id,
        private readonly PromptTemplateType $type,
        private readonly string $version,
        private readonly string $body,
        private readonly string $description = '',
    ) {}

    public function id(): PromptTemplateId
    {
        return $this->id;
    }

    public function type(): PromptTemplateType
    {
        return $this->type;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function description(): string
    {
        return $this->description;
    }

    /**
     * @param  array<string, scalar|null>  $variables
     */
    public function render(array $variables): string
    {
        $result = $this->body;
        foreach ($variables as $key => $value) {
            $result = str_replace('{{'.$key.'}}', (string) ($value ?? ''), $result);
        }

        // Strip unresolved optional placeholders.
        return (string) preg_replace('/\{\{[a-zA-Z0-9_]+\}\}/', '', $result);
    }
}

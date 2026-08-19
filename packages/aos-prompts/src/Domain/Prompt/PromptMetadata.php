<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Prompt;

/**
 * Typed metadata bag for a generated prompt (value object).
 */
final class PromptMetadata
{
    /**
     * @param  array<string, scalar|null>  $attributes
     */
    public function __construct(
        private readonly array $attributes = [],
    ) {}

    public static function fromDocument(PromptDocument $document): self
    {
        return new self($document->metadata());
    }

    /**
     * @return array<string, scalar|null>
     */
    public function all(): array
    {
        return $this->attributes;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function correlationId(): ?string
    {
        $value = $this->attributes['correlation_id'] ?? null;

        return is_string($value) ? $value : null;
    }

    public function tenantId(): ?string
    {
        $value = $this->attributes['tenant_id'] ?? null;

        return is_string($value) ? $value : null;
    }
}

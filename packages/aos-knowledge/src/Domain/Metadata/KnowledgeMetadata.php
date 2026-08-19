<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Metadata;

final class KnowledgeMetadata
{
    /**
     * @param  array<string, scalar|null>  $attributes
     */
    public function __construct(
        private readonly array $attributes = [],
    ) {}

    /**
     * @param  array<string, scalar|null>  $attributes
     */
    public static function from(array $attributes): self
    {
        return new self($attributes);
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

    /**
     * @param  array<string, scalar|null>  $attributes
     */
    public function merge(array $attributes): self
    {
        return new self(array_merge($this->attributes, $attributes));
    }
}

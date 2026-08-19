<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Tool;

/**
 * Conceptual JSON-like schema descriptor (no runtime JSON Schema engine required).
 *
 * @phpstan-type SchemaShape array{type?: string, required?: list<string>, properties?: array<string, mixed>}
 */
final class ConceptualSchema
{
    /**
     * @param  SchemaShape  $shape
     */
    public function __construct(
        private readonly array $shape = [],
    ) {}

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @param  SchemaShape  $shape
     */
    public static function of(array $shape): self
    {
        return new self($shape);
    }

    /**
     * @return SchemaShape
     */
    public function shape(): array
    {
        return $this->shape;
    }

    /**
     * @return list<string>
     */
    public function requiredFields(): array
    {
        /** @var list<string> $required */
        $required = $this->shape['required'] ?? [];

        return $required;
    }
}

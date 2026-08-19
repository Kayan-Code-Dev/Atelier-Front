<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Business;

/**
 * Opaque business-state slice (no Tenant Ops coupling — placeholders only).
 */
final class BusinessContext
{
    /**
     * @param  array<string, scalar|null>  $facts
     */
    public function __construct(
        private readonly array $facts = [],
        private readonly ?string $currentStateSummary = null,
    ) {}

    public static function empty(): self
    {
        return new self();
    }

    /**
     * @return array<string, scalar|null>
     */
    public function facts(): array
    {
        return $this->facts;
    }

    public function currentStateSummary(): ?string
    {
        return $this->currentStateSummary;
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Tool;

/**
 * Immutable tool descriptor published to the platform registry.
 */
final class ToolDescriptor
{
    public function __construct(
        private readonly ToolMetadata $metadata,
        private readonly string $providerId,
        private readonly ?string $permission = null,
    ) {}

    public function metadata(): ToolMetadata { return $this->metadata; }
    public function name(): string { return $this->metadata->toolName(); }
    public function version(): ToolVersion { return $this->metadata->version(); }
    public function providerId(): string { return $this->providerId; }
    public function permission(): ?string { return $this->permission; }
    public function category(): ToolCategory { return $this->metadata->category(); }
    /** @return list<string> */
    public function capabilities(): array { return $this->metadata->capabilities(); }

    public function isDiscoverable(): bool
    {
        return $this->metadata->status() === ToolStatus::Active
            && $this->metadata->availability() !== ToolAvailability::Unavailable
            && $this->metadata->visibility() !== ToolVisibility::Restricted;
    }
}

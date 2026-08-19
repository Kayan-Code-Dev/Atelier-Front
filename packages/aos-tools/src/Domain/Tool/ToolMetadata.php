<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Tool;

/**
 * Loaded tool metadata snapshot used during execution.
 */
final class ToolMetadata
{
    /**
     * @param  list<string>  $capabilities
     * @param  list<string>  $permissions
     * @param  list<string>  $supportedIntents
     */
    public function __construct(
        private readonly ToolIdentifier $identifier,
        private readonly ToolVersion $version,
        private readonly ToolCategoryCode $category,
        private readonly string $description,
        private readonly ToolRiskLevel $riskLevel,
        private readonly array $capabilities,
        private readonly array $permissions,
        private readonly array $supportedIntents,
    ) {}

    public function identifier(): ToolIdentifier
    {
        return $this->identifier;
    }

    public function version(): ToolVersion
    {
        return $this->version;
    }

    public function category(): ToolCategoryCode
    {
        return $this->category;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function riskLevel(): ToolRiskLevel
    {
        return $this->riskLevel;
    }

    /**
     * @return list<string>
     */
    public function capabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return $this->permissions;
    }

    /**
     * @return list<string>
     */
    public function supportedIntents(): array
    {
        return $this->supportedIntents;
    }
}

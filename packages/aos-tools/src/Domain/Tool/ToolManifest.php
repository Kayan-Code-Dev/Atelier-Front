<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Tool;

/**
 * Immutable registration manifest for a Business Tool (identifier-keyed, not class-keyed).
 */
final class ToolManifest
{
    /**
     * @param  list<string>  $capabilities
     * @param  list<string>  $permissions
     * @param  list<ToolOperatingMode>  $operatingModes
     * @param  list<string>  $supportedIntents
     */
    public function __construct(
        private readonly ToolIdentifier $identifier,
        private readonly ToolVersion $version,
        private readonly ToolCategoryCode $category,
        private readonly string $description,
        private readonly array $capabilities,
        private readonly array $permissions,
        private readonly array $operatingModes,
        private readonly ToolRiskLevel $riskLevel,
        private readonly array $supportedIntents,
        private readonly ConceptualSchema $inputSchema = new ConceptualSchema([]),
        private readonly ConceptualSchema $outputSchema = new ConceptualSchema([]),
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
     * @return list<ToolOperatingMode>
     */
    public function operatingModes(): array
    {
        return $this->operatingModes;
    }

    public function riskLevel(): ToolRiskLevel
    {
        return $this->riskLevel;
    }

    /**
     * @return list<string>
     */
    public function supportedIntents(): array
    {
        return $this->supportedIntents;
    }

    public function inputSchema(): ConceptualSchema
    {
        return $this->inputSchema;
    }

    public function outputSchema(): ConceptualSchema
    {
        return $this->outputSchema;
    }

    public function supportsMode(ToolOperatingMode $mode): bool
    {
        return in_array($mode, $this->operatingModes, true);
    }

    public function toMetadata(): ToolMetadata
    {
        return new ToolMetadata(
            $this->identifier,
            $this->version,
            $this->category,
            $this->description,
            $this->riskLevel,
            $this->capabilities,
            $this->permissions,
            $this->supportedIntents,
        );
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Tool;

use DressnMore\Aos\Tools\Domain\Tool\ToolRiskLevel;

/**
 * Rich metadata for Planner / Prompt / Workspace discovery (not execution).
 */
final class ToolMetadata
{
    /**
     * @param list<string> $capabilities
     * @param list<string> $operatingModes
     * @param list<string> $inputContract
     * @param list<string> $outputContract
     * @param list<string> $events
     * @param list<string> $dependencies
     * @param list<string> $tags
     */
    public function __construct(
        private readonly string $toolName,
        private readonly ToolVersion $version,
        private readonly string $ownerDomain,
        private readonly string $description,
        private readonly ToolCategory $category,
        private readonly array $capabilities,
        private readonly ToolRiskLevel $riskLevel,
        private readonly ApprovalRequirement $approvalRequirement,
        private readonly array $operatingModes,
        private readonly array $inputContract,
        private readonly array $outputContract,
        private readonly array $events = [],
        private readonly array $dependencies = [],
        private readonly ToolStatus $status = ToolStatus::Active,
        private readonly ToolAvailability $availability = ToolAvailability::Available,
        private readonly ToolVisibility $visibility = ToolVisibility::Public,
        private readonly array $tags = [],
    ) {}

    public function toolName(): string { return $this->toolName; }
    public function version(): ToolVersion { return $this->version; }
    public function ownerDomain(): string { return $this->ownerDomain; }
    public function description(): string { return $this->description; }
    public function category(): ToolCategory { return $this->category; }
    /** @return list<string> */
    public function capabilities(): array { return $this->capabilities; }
    public function riskLevel(): ToolRiskLevel { return $this->riskLevel; }
    public function approvalRequirement(): ApprovalRequirement { return $this->approvalRequirement; }
    /** @return list<string> */
    public function operatingModes(): array { return $this->operatingModes; }
    /** @return list<string> */
    public function inputContract(): array { return $this->inputContract; }
    /** @return list<string> */
    public function outputContract(): array { return $this->outputContract; }
    /** @return list<string> */
    public function events(): array { return $this->events; }
    /** @return list<string> */
    public function dependencies(): array { return $this->dependencies; }
    public function status(): ToolStatus { return $this->status; }
    public function availability(): ToolAvailability { return $this->availability; }
    public function visibility(): ToolVisibility { return $this->visibility; }
    /** @return list<string> */
    public function tags(): array { return $this->tags; }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'toolName' => $this->toolName,
            'version' => $this->version->toString(),
            'ownerDomain' => $this->ownerDomain,
            'description' => $this->description,
            'category' => $this->category->value,
            'capabilities' => $this->capabilities,
            'riskLevel' => $this->riskLevel->value,
            'approvalRequirement' => $this->approvalRequirement->value,
            'operatingModes' => $this->operatingModes,
            'inputContract' => $this->inputContract,
            'outputContract' => $this->outputContract,
            'events' => $this->events,
            'dependencies' => $this->dependencies,
            'status' => $this->status->value,
            'availability' => $this->availability->value,
            'visibility' => $this->visibility->value,
            'tags' => $this->tags,
        ];
    }
}

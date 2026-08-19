<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Context;

use DressnMore\Aos\Tools\Domain\Request\CorrelationId;
use DressnMore\Aos\Tools\Domain\Tool\ToolOperatingMode;

/**
 * Execution context assembled for one tool invocation.
 * Context Snapshot is referenced opaquely (no coupling to Context Engine types).
 */
final class ToolExecutionContext
{
    /**
     * @param  list<string>  $resolvedPermissions
     * @param  list<string>  $capabilities
     * @param  array<string, scalar|null>  $executionMetadata
     * @param  array<string, mixed>  $contextSnapshot  opaque snapshot payload / refs
     */
    public function __construct(
        private readonly CorrelationId $correlationId,
        private readonly ToolOperatingMode $operatingMode,
        private readonly array $resolvedPermissions = [],
        private readonly array $capabilities = [],
        private readonly array $contextSnapshot = [],
        private readonly array $executionMetadata = [],
    ) {}

    /**
     * @param  list<string>  $resolvedPermissions
     * @param  list<string>  $capabilities
     * @param  array<string, scalar|null>  $executionMetadata
     * @param  array<string, mixed>  $contextSnapshot
     */
    public static function create(
        ToolOperatingMode $operatingMode,
        array $resolvedPermissions = [],
        array $capabilities = [],
        array $contextSnapshot = [],
        array $executionMetadata = [],
        ?CorrelationId $correlationId = null,
    ): self {
        return new self(
            $correlationId ?? CorrelationId::generate(),
            $operatingMode,
            $resolvedPermissions,
            $capabilities,
            $contextSnapshot,
            $executionMetadata,
        );
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function operatingMode(): ToolOperatingMode
    {
        return $this->operatingMode;
    }

    /**
     * @return list<string>
     */
    public function resolvedPermissions(): array
    {
        return $this->resolvedPermissions;
    }

    /**
     * @return list<string>
     */
    public function capabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * @return array<string, mixed>
     */
    public function contextSnapshot(): array
    {
        return $this->contextSnapshot;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function executionMetadata(): array
    {
        return $this->executionMetadata;
    }

    public function allowsPermission(string $permission): bool
    {
        return in_array($permission, $this->resolvedPermissions, true);
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }
}

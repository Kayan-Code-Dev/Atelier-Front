<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Factories;

use DressnMore\Aos\Tools\Domain\Context\ToolExecutionContext;
use DressnMore\Aos\Tools\Domain\Request\CorrelationId;
use DressnMore\Aos\Tools\Domain\Request\RequestedBy;
use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;
use DressnMore\Aos\Tools\Domain\Tool\ToolOperatingMode;

final class ToolRequestFactory
{
    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $permissions
     * @param  list<string>  $capabilities
     * @param  array<string, mixed>  $contextSnapshot
     * @param  array<string, scalar|null>  $metadata
     */
    public function make(
        string $toolIdentifier,
        array $input,
        ToolOperatingMode $mode = ToolOperatingMode::Assistant,
        array $permissions = [],
        array $capabilities = [],
        array $contextSnapshot = [],
        ?string $tenantId = null,
        ?string $customerId = null,
        ?string $conversationId = null,
        ?RequestedBy $requestedBy = null,
        array $metadata = [],
    ): ToolRequest {
        $context = ToolExecutionContext::create(
            $mode,
            $permissions,
            $capabilities,
            $contextSnapshot,
        );

        return ToolRequest::create(
            ToolIdentifier::fromString($toolIdentifier),
            $context,
            $input,
            $requestedBy ?? RequestedBy::planner(),
            CorrelationId::generate(),
            $conversationId,
            $tenantId,
            $customerId,
            $metadata,
        );
    }
}

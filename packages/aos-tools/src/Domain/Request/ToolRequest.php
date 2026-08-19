<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Request;

use DateTimeImmutable;
use DressnMore\Aos\Tools\Domain\Context\ToolExecutionContext;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;
use InvalidArgumentException;

/**
 * Immutable tool invocation request.
 */
final class ToolRequest
{
    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, scalar|null>  $metadata
     */
    public function __construct(
        private readonly ToolIdentifier $toolIdentifier,
        private readonly ToolExecutionContext $executionContext,
        private readonly array $input,
        private readonly RequestedBy $requestedBy,
        private readonly CorrelationId $correlationId,
        private readonly ?string $conversationId = null,
        private readonly ?string $tenantId = null,
        private readonly ?string $customerId = null,
        private readonly array $metadata = [],
        private readonly DateTimeImmutable $requestedAt = new DateTimeImmutable(),
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, scalar|null>  $metadata
     */
    public static function create(
        ToolIdentifier $toolIdentifier,
        ToolExecutionContext $executionContext,
        array $input,
        RequestedBy $requestedBy,
        ?CorrelationId $correlationId = null,
        ?string $conversationId = null,
        ?string $tenantId = null,
        ?string $customerId = null,
        array $metadata = [],
    ): self {
        return new self(
            $toolIdentifier,
            $executionContext,
            $input,
            $requestedBy,
            $correlationId ?? CorrelationId::generate(),
            $conversationId,
            $tenantId,
            $customerId,
            $metadata,
        );
    }

    public function toolIdentifier(): ToolIdentifier
    {
        return $this->toolIdentifier;
    }

    public function executionContext(): ToolExecutionContext
    {
        return $this->executionContext;
    }

    /**
     * @return array<string, mixed>
     */
    public function input(): array
    {
        return $this->input;
    }

    public function requestedBy(): RequestedBy
    {
        return $this->requestedBy;
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function conversationId(): ?string
    {
        return $this->conversationId;
    }

    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    public function customerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function requestedAt(): DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function requireInputKey(string $key): mixed
    {
        if (! array_key_exists($key, $this->input)) {
            throw new InvalidArgumentException(sprintf('Missing input key [%s].', $key));
        }

        return $this->input[$key];
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Result;

/**
 * Immutable normalized tool result.
 */
final class ToolResult
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $warnings
     * @param  list<ToolFailure>  $errors
     */
    public function __construct(
        private readonly ExecutionStatus $status,
        private readonly float $executionTimeMs,
        private readonly array $payload = [],
        private readonly array $warnings = [],
        private readonly array $errors = [],
        private readonly ?string $auditReference = null,
        private readonly ?string $analyticsReference = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $warnings
     */
    public static function success(
        array $payload = [],
        float $executionTimeMs = 0.0,
        array $warnings = [],
        ?string $auditReference = null,
        ?string $analyticsReference = null,
    ): self {
        return new self(
            ExecutionStatus::Success,
            $executionTimeMs,
            $payload,
            $warnings,
            [],
            $auditReference,
            $analyticsReference,
        );
    }

    /**
     * @param  list<ToolFailure>  $errors
     */
    public static function failed(
        array $errors,
        float $executionTimeMs = 0.0,
        ExecutionStatus $status = ExecutionStatus::Failed,
        ?string $auditReference = null,
        ?string $analyticsReference = null,
    ): self {
        return new self(
            $status,
            $executionTimeMs,
            [],
            [],
            $errors,
            $auditReference,
            $analyticsReference,
        );
    }

    public function status(): ExecutionStatus
    {
        return $this->status;
    }

    public function executionTimeMs(): float
    {
        return $this->executionTimeMs;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * @return list<ToolFailure>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function auditReference(): ?string
    {
        return $this->auditReference;
    }

    public function analyticsReference(): ?string
    {
        return $this->analyticsReference;
    }

    public function isSuccess(): bool
    {
        return $this->status === ExecutionStatus::Success;
    }

    /**
     * Returns a new result with audit/analytics refs (immutability-preserving).
     */
    public function withReferences(?string $auditReference, ?string $analyticsReference): self
    {
        return new self(
            $this->status,
            $this->executionTimeMs,
            $this->payload,
            $this->warnings,
            $this->errors,
            $auditReference ?? $this->auditReference,
            $analyticsReference ?? $this->analyticsReference,
        );
    }

    public function withExecutionTime(float $ms): self
    {
        return new self(
            $this->status,
            $ms,
            $this->payload,
            $this->warnings,
            $this->errors,
            $this->auditReference,
            $this->analyticsReference,
        );
    }
}

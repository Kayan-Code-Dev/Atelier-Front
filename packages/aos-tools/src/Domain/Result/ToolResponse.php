<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Result;

/**
 * Ubiquitous Language alias for the immutable tool response payload.
 *
 * Prefer {@see ToolResult} at type boundaries; this wrapper documents the "Tool Response" term.
 */
final class ToolResponse
{
    public function __construct(
        private readonly ToolResult $result,
    ) {}

    public static function fromResult(ToolResult $result): self
    {
        return new self($result);
    }

    public function result(): ToolResult
    {
        return $this->result;
    }

    public function status(): ExecutionStatus
    {
        return $this->result->status();
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->result->payload();
    }
}

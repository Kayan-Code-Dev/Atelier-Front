<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Result;

/**
 * Structured tool failure model.
 */
final class ToolFailure
{
    public function __construct(
        private readonly string $code,
        private readonly string $message,
        private readonly bool $retryable = false,
    ) {}

    public static function of(string $code, string $message, bool $retryable = false): self
    {
        return new self($code, $message, $retryable);
    }

    public function code(): string
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }
}

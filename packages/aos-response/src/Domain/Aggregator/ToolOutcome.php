<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Domain\Aggregator;

/**
 * Normalized tool outcome for aggregation (decoupled from Gateway internals).
 */
final class ToolOutcome
{
    /**
     * @param array<string, mixed> $payload
     * @param list<string> $warnings
     * @param list<array{code:string,message:string}> $errors
     */
    public function __construct(
        private readonly string $toolName,
        private readonly bool $success,
        private readonly array $payload = [],
        private readonly array $warnings = [],
        private readonly array $errors = [],
        private readonly string $status = 'success',
        private readonly int $order = 0,
    ) {}

    public function toolName(): string { return $this->toolName; }
    public function success(): bool { return $this->success; }
    /** @return array<string, mixed> */
    public function payload(): array { return $this->payload; }
    /** @return list<string> */
    public function warnings(): array { return $this->warnings; }
    /** @return list<array{code:string,message:string}> */
    public function errors(): array { return $this->errors; }
    public function status(): string { return $this->status; }
    public function order(): int { return $this->order; }

    public function primaryErrorCode(): ?string
    {
        return $this->errors[0]['code'] ?? null;
    }

    public function primaryErrorMessage(): ?string
    {
        return $this->errors[0]['message'] ?? null;
    }
}

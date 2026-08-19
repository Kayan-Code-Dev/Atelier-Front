<?php

declare(strict_types=1);

namespace DressnMore\Aos\Response\Domain\Response;

/**
 * Unified user-facing AI reply (Sprint 20).
 */
final class FinalAiResponse
{
    /**
     * @param list<string> $sections
     * @param array<string, mixed> $data
     * @param list<string> $warnings
     */
    public function __construct(
        private readonly string $message,
        private readonly ResponseStatus $status,
        private readonly string $locale,
        private readonly array $sections = [],
        private readonly array $data = [],
        private readonly array $warnings = [],
        private readonly ?string $planId = null,
        private readonly ?string $correlationId = null,
    ) {}

    public function message(): string { return $this->message; }
    public function status(): ResponseStatus { return $this->status; }
    public function locale(): string { return $this->locale; }
    /** @return list<string> */
    public function sections(): array { return $this->sections; }
    /** @return array<string, mixed> */
    public function data(): array { return $this->data; }
    /** @return list<string> */
    public function warnings(): array { return $this->warnings; }
    public function planId(): ?string { return $this->planId; }
    public function correlationId(): ?string { return $this->correlationId; }

    public function isSuccess(): bool
    {
        return in_array($this->status, [ResponseStatus::Success, ResponseStatus::PartialSuccess], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'status' => $this->status->value,
            'locale' => $this->locale,
            'sections' => $this->sections,
            'data' => $this->data,
            'warnings' => $this->warnings,
            'planId' => $this->planId,
            'correlationId' => $this->correlationId,
        ];
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Guard;

final class GuardResult
{
    /**
     * @param  list<string>  $triggers
     */
    public function __construct(
        private readonly GuardVerdict $verdict,
        private readonly array $triggers = [],
        private readonly ?string $sanitizedMessage = null,
    ) {}

    public static function allow(): self
    {
        return new self(GuardVerdict::Allow);
    }

    /**
     * @param  list<string>  $triggers
     */
    public static function sanitize(string $message, array $triggers): self
    {
        return new self(GuardVerdict::Sanitize, $triggers, $message);
    }

    /**
     * @param  list<string>  $triggers
     */
    public static function reject(array $triggers): self
    {
        return new self(GuardVerdict::Reject, $triggers);
    }

    public function verdict(): GuardVerdict
    {
        return $this->verdict;
    }

    /**
     * @return list<string>
     */
    public function triggers(): array
    {
        return $this->triggers;
    }

    public function sanitizedMessage(): ?string
    {
        return $this->sanitizedMessage;
    }

    public function isRejected(): bool
    {
        return $this->verdict === GuardVerdict::Reject;
    }
}

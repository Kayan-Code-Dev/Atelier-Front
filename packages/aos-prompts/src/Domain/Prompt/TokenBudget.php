<?php

declare(strict_types=1);

namespace DressnMore\Aos\Prompts\Domain\Prompt;

/**
 * Conceptual token budget (no provider tokenizer).
 */
final class TokenBudget
{
    public function __construct(
        private readonly int $maxTokens = 8000,
        private readonly int $estimatedTokens = 0,
    ) {}

    public function maxTokens(): int
    {
        return $this->maxTokens;
    }

    public function estimatedTokens(): int
    {
        return $this->estimatedTokens;
    }

    public function exceeds(): bool
    {
        return $this->estimatedTokens > $this->maxTokens;
    }

    public function withEstimate(int $estimated): self
    {
        return new self($this->maxTokens, max(0, $estimated));
    }

    /**
     * Rough heuristic: ~4 chars per token.
     */
    public static function estimateFromText(string $text, int $maxTokens = 8000): self
    {
        $estimated = (int) ceil(mb_strlen($text) / 4);

        return new self($maxTokens, $estimated);
    }
}

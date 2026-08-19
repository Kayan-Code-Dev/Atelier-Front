<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Token;

final class TokenUsage
{
    public function __construct(
        private readonly int $promptTokens,
        private readonly int $completionTokens,
    ) {}

    public static function estimateFromText(string $prompt, string $completion): self
    {
        return new self(
            max(1, (int) ceil(mb_strlen($prompt) / 4)),
            max(1, (int) ceil(mb_strlen($completion) / 4)),
        );
    }

    public function promptTokens(): int
    {
        return $this->promptTokens;
    }

    public function completionTokens(): int
    {
        return $this->completionTokens;
    }

    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }

    /** @return array{prompt_tokens: int, completion_tokens: int, total_tokens: int} */
    public function toArray(): array
    {
        return [
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens' => $this->totalTokens(),
        ];
    }
}

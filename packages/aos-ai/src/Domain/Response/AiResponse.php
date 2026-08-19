<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Response;

use DressnMore\Aos\Ai\Domain\Model\ModelId;
use DressnMore\Aos\Ai\Domain\Provider\ProviderId;
use DressnMore\Aos\Ai\Domain\Token\TokenUsage;

enum FinishReason: string
{
    case Stop = 'stop';
    case Length = 'length';
    case ContentFilter = 'content_filter';
    case Error = 'error';
    case Fallback = 'fallback';
}

final class AiResponse
{
    /**
     * @param  array<string, scalar|null>  $metadata
     */
    public function __construct(
        private readonly string $completion,
        private readonly ProviderId $providerId,
        private readonly ModelId $modelId,
        private readonly TokenUsage $usage,
        private readonly int $latencyMs,
        private readonly float $costUsd,
        private readonly FinishReason $finishReason = FinishReason::Stop,
        private readonly array $metadata = [],
        private readonly bool $fromFallback = false,
    ) {}

    public function completion(): string
    {
        return $this->completion;
    }

    public function providerId(): ProviderId
    {
        return $this->providerId;
    }

    public function modelId(): ModelId
    {
        return $this->modelId;
    }

    public function usage(): TokenUsage
    {
        return $this->usage;
    }

    public function latencyMs(): int
    {
        return $this->latencyMs;
    }

    public function costUsd(): float
    {
        return $this->costUsd;
    }

    public function finishReason(): FinishReason
    {
        return $this->finishReason;
    }

    /** @return array<string, scalar|null> */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function fromFallback(): bool
    {
        return $this->fromFallback;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'completion' => $this->completion,
            'provider' => $this->providerId->toString(),
            'model' => $this->modelId->toString(),
            'usage' => $this->usage->toArray(),
            'latency_ms' => $this->latencyMs,
            'cost_usd' => $this->costUsd,
            'finish_reason' => $this->finishReason->value,
            'from_fallback' => $this->fromFallback,
            'metadata' => $this->metadata,
        ];
    }
}

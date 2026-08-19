<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Request;

use DressnMore\Aos\Ai\Domain\Capability\ModelCapability;
use DressnMore\Aos\Ai\Domain\Model\ModelId;
use DressnMore\Aos\Ai\Domain\Provider\ProviderId;

/**
 * Provider-agnostic AI request (opaque conversation/prompt payloads).
 */
final class AiRequest
{
    /**
     * @param  list<array{role: string, content: string}>  $conversation
     * @param  list<ModelCapability>  $requiredCapabilities
     * @param  array<string, scalar|null>  $metadata
     */
    public function __construct(
        private readonly string $prompt,
        private readonly array $conversation = [],
        private readonly string $context = '',
        private readonly array $requiredCapabilities = [ModelCapability::ChatCompletion],
        private readonly ?ProviderId $preferredProviderId = null,
        private readonly ?ModelId $preferredModelId = null,
        private readonly bool $streaming = false,
        private readonly float $temperature = 0.2,
        private readonly int $maxTokens = 1024,
        private readonly ?string $tenantId = null,
        private readonly float $maxBudgetUsd = 1.0,
        private readonly int $maxLatencyMs = 5000,
        private readonly array $metadata = [],
        private readonly string $correlationId = '',
    ) {}

    /**
     * @param  list<array{role: string, content: string}>  $conversation
     * @param  list<ModelCapability>  $requiredCapabilities
     * @param  array<string, scalar|null>  $metadata
     */
    public static function create(
        string $prompt,
        array $conversation = [],
        string $context = '',
        array $requiredCapabilities = [ModelCapability::ChatCompletion],
        ?ProviderId $preferredProviderId = null,
        ?ModelId $preferredModelId = null,
        bool $streaming = false,
        float $temperature = 0.2,
        int $maxTokens = 1024,
        ?string $tenantId = null,
        float $maxBudgetUsd = 1.0,
        int $maxLatencyMs = 5000,
        array $metadata = [],
        ?string $correlationId = null,
    ): self {
        return new self(
            $prompt,
            $conversation,
            $context,
            $requiredCapabilities,
            $preferredProviderId,
            $preferredModelId,
            $streaming,
            $temperature,
            max(1, $maxTokens),
            $tenantId,
            max(0.0, $maxBudgetUsd),
            max(1, $maxLatencyMs),
            $metadata,
            $correlationId ?? bin2hex(random_bytes(12)),
        );
    }

    public function prompt(): string
    {
        return $this->prompt;
    }

    /** @return list<array{role: string, content: string}> */
    public function conversation(): array
    {
        return $this->conversation;
    }

    public function context(): string
    {
        return $this->context;
    }

    /** @return list<ModelCapability> */
    public function requiredCapabilities(): array
    {
        return $this->requiredCapabilities;
    }

    public function preferredProviderId(): ?ProviderId
    {
        return $this->preferredProviderId;
    }

    public function preferredModelId(): ?ModelId
    {
        return $this->preferredModelId;
    }

    public function streaming(): bool
    {
        return $this->streaming;
    }

    public function temperature(): float
    {
        return $this->temperature;
    }

    public function maxTokens(): int
    {
        return $this->maxTokens;
    }

    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    public function maxBudgetUsd(): float
    {
        return $this->maxBudgetUsd;
    }

    public function maxLatencyMs(): int
    {
        return $this->maxLatencyMs;
    }

    /** @return array<string, scalar|null> */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function withPreferredProvider(?ProviderId $providerId): self
    {
        return new self(
            $this->prompt,
            $this->conversation,
            $this->context,
            $this->requiredCapabilities,
            $providerId,
            $this->preferredModelId,
            $this->streaming,
            $this->temperature,
            $this->maxTokens,
            $this->tenantId,
            $this->maxBudgetUsd,
            $this->maxLatencyMs,
            $this->metadata,
            $this->correlationId,
        );
    }

    public function withPreferredModel(?ModelId $modelId): self
    {
        return new self(
            $this->prompt,
            $this->conversation,
            $this->context,
            $this->requiredCapabilities,
            $this->preferredProviderId,
            $modelId,
            $this->streaming,
            $this->temperature,
            $this->maxTokens,
            $this->tenantId,
            $this->maxBudgetUsd,
            $this->maxLatencyMs,
            $this->metadata,
            $this->correlationId,
        );
    }
}

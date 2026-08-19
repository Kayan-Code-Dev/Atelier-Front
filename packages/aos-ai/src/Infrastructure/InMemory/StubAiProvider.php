<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Infrastructure\InMemory;

use DressnMore\Aos\Ai\Contracts\AiProviderInterface;
use DressnMore\Aos\Ai\Domain\Model\ModelId;
use DressnMore\Aos\Ai\Domain\Provider\ProviderId;
use DressnMore\Aos\Ai\Domain\Provider\ProviderKind;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;
use DressnMore\Aos\Ai\Domain\Response\AiResponse;
use DressnMore\Aos\Ai\Domain\Response\FinishReason;
use DressnMore\Aos\Ai\Domain\Streaming\StreamChunk;
use DressnMore\Aos\Ai\Domain\Token\TokenUsage;
use RuntimeException;

/**
 * Deterministic stub provider — no network, no SDK.
 */
final class StubAiProvider implements AiProviderInterface
{
    public function __construct(
        private readonly string $id,
        private readonly ProviderKind $kind,
        private readonly bool $fail = false,
        private bool $available = true,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function kind(): ProviderKind
    {
        return $this->kind;
    }

    public function setAvailable(bool $available): void
    {
        $this->available = $available;
    }

    public function isAvailable(): bool
    {
        return $this->available && ! $this->fail;
    }

    public function complete(AiRequest $request): AiResponse
    {
        if (! $this->isAvailable()) {
            throw new RuntimeException('Provider unavailable: '.$this->id);
        }

        $modelId = $request->preferredModelId()?->toString() ?? $this->id.'-default';
        $text = sprintf(
            '[%s/%s] %s',
            $this->kind->value,
            $modelId,
            trim($request->prompt()) !== '' ? 'AOS stub completion for: '.mb_substr($request->prompt(), 0, 120) : 'Empty prompt'
        );
        $usage = TokenUsage::estimateFromText($request->prompt().$request->context(), $text);
        $latency = 50 + random_int(0, 40);
        $cost = ($usage->promptTokens() / 1000) * 0.001 + ($usage->completionTokens() / 1000) * 0.002;

        return new AiResponse(
            $text,
            ProviderId::fromString($this->id),
            ModelId::fromString($modelId),
            $usage,
            $latency,
            round($cost, 6),
            FinishReason::Stop,
            ['stub' => true, 'provider_kind' => $this->kind->value],
        );
    }

    public function stream(AiRequest $request): iterable
    {
        if (! $this->isAvailable()) {
            throw new RuntimeException('Provider unavailable: '.$this->id);
        }

        $full = $this->complete($request)->completion();
        $parts = preg_split('/\s+/u', $full) ?: [$full];
        $index = 0;
        foreach ($parts as $part) {
            yield new StreamChunk($part.' ', false, $index++);
        }
        yield new StreamChunk('', true, $index);
    }
}

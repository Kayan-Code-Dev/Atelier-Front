<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Factory;

use DressnMore\Aos\Ai\Contracts\AiProviderInterface;
use DressnMore\Aos\Ai\Domain\Provider\ProviderDescriptor;
use DressnMore\Aos\Ai\Domain\Provider\ProviderKind;
use DressnMore\Aos\Ai\Infrastructure\InMemory\StubAiProvider;
use InvalidArgumentException;

/**
 * Creates stub provider plugins — no HTTP / SDK.
 */
final class ProviderFactory
{
    public function createStub(ProviderDescriptor $descriptor, bool $fail = false): AiProviderInterface
    {
        return new StubAiProvider($descriptor->id()->toString(), $descriptor->kind(), $fail);
    }

    public function createForKind(ProviderKind $kind, string $id, bool $fail = false): AiProviderInterface
    {
        return new StubAiProvider($id, $kind, $fail);
    }

    public function assertNoSdk(): void
    {
        // Architectural guardrail marker for documentation / tests.
        if (class_exists('OpenAI\\Client', false)) {
            throw new InvalidArgumentException('OpenAI SDK must not be loaded by aos-ai core.');
        }
    }
}

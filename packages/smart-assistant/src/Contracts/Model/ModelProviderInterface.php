<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Contracts\Model;

interface ModelProviderInterface
{
    public function providerId(): string;

    /**
     * Contract only — no LLM calls in Sprint 21.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function complete(array $input): array;
}

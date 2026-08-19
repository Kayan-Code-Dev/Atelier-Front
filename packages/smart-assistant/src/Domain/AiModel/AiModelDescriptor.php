<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\AiModel;

final class AiModelDescriptor
{
    public function __construct(
        private readonly string $id,
        private readonly string $provider,
        private readonly string $modelName,
        private readonly bool $enabled = true,
    ) {}

    public function id(): string { return $this->id; }
    public function provider(): string { return $this->provider; }
    public function modelName(): string { return $this->modelName; }
    public function enabled(): bool { return $this->enabled; }
}

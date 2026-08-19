<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Channel;

final class Channel
{
    public function __construct(
        private readonly string $id,
        private readonly string $type,
        private readonly string $name,
        private readonly bool $enabled = true,
    ) {}

    public function id(): string { return $this->id; }
    public function type(): string { return $this->type; }
    public function name(): string { return $this->name; }
    public function enabled(): bool { return $this->enabled; }
}

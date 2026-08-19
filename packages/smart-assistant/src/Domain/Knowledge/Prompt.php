<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Knowledge;

final class Prompt
{
    public function __construct(
        private readonly string $id,
        private readonly string $key,
        private readonly string $locale,
        private readonly string $body,
        private readonly ?string $tenantId = null,
    ) {}

    public function id(): string { return $this->id; }
    public function key(): string { return $this->key; }
    public function locale(): string { return $this->locale; }
    public function body(): string { return $this->body; }
    public function tenantId(): ?string { return $this->tenantId; }
}

<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Domain\Integration;

final class IntegrationProvider
{
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly string $category,
    ) {}

    public function id(): string { return $this->id; }
    public function name(): string { return $this->name; }
    public function category(): string { return $this->category; }
}

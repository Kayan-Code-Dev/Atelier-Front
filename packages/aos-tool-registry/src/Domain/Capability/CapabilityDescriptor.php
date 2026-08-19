<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Capability;

final class CapabilityDescriptor
{
    public function __construct(
        private readonly string $name,
        private readonly string $ownerDomain,
        private readonly string $description,
        private readonly bool $write = false,
    ) {}

    public function name(): string { return $this->name; }
    public function ownerDomain(): string { return $this->ownerDomain; }
    public function description(): string { return $this->description; }
    public function write(): bool { return $this->write; }
}

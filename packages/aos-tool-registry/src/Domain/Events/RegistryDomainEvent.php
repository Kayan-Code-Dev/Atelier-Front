<?php

declare(strict_types=1);

namespace DressnMore\Aos\ToolRegistry\Domain\Events;

use DateTimeImmutable;

final class RegistryDomainEvent
{
    /**
     * @param array<string, scalar|null> $payload
     */
    public function __construct(
        private readonly string $name,
        private readonly array $payload = [],
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {}

    public function name(): string { return $this->name; }
    /** @return array<string, scalar|null> */
    public function payload(): array { return $this->payload; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }

    public static function toolRegistered(array $payload = []): self { return new self('ToolRegisteredInPlatform', $payload); }
    public static function capabilityRegistered(array $payload = []): self { return new self('CapabilityRegistered', $payload); }
    public static function intentRegistered(array $payload = []): self { return new self('IntentRegistered', $payload); }
    public static function discoveryRejected(array $payload = []): self { return new self('ToolDiscoveryRejected', $payload); }
    public static function capabilityDenied(array $payload = []): self { return new self('CapabilityDenied', $payload); }
    public static function versionIncompatible(array $payload = []): self { return new self('ToolVersionIncompatible', $payload); }
}

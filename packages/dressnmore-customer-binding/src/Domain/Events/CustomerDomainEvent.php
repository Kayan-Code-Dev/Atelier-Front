<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Domain\Events;

use DateTimeImmutable;

final class CustomerDomainEvent
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

    public static function customerResolved(array $payload = []): self { return new self('CustomerResolved', $payload); }
    public static function customerCreated(array $payload = []): self { return new self('CustomerCreated', $payload); }
    public static function customerUpdated(array $payload = []): self { return new self('CustomerUpdated', $payload); }
    public static function customerMerged(array $payload = []): self { return new self('CustomerMerged', $payload); }
    public static function customerSummaryBuilt(array $payload = []): self { return new self('CustomerSummaryBuilt', $payload); }
    public static function customerContextBuilt(array $payload = []): self { return new self('CustomerContextBuilt', $payload); }
    public static function customerSnapshotBuilt(array $payload = []): self { return new self('CustomerSnapshotBuilt', $payload); }
    public static function customerTimelineBuilt(array $payload = []): self { return new self('CustomerTimelineBuilt', $payload); }
}

<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Domain\Events;

use DateTimeImmutable;

final class ReservationDomainEvent
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

    public static function reservationCreated(array $payload = []): self { return new self('ReservationCreated', $payload); }
    public static function reservationUpdated(array $payload = []): self { return new self('ReservationUpdated', $payload); }
    public static function reservationCancelled(array $payload = []): self { return new self('ReservationCancelled', $payload); }
    public static function reservationConfirmed(array $payload = []): self { return new self('ReservationConfirmed', $payload); }
    public static function reservationRescheduled(array $payload = []): self { return new self('ReservationRescheduled', $payload); }
    public static function reservationReminderScheduled(array $payload = []): self { return new self('ReservationReminderScheduled', $payload); }
    public static function reservationContextBuilt(array $payload = []): self { return new self('ReservationContextBuilt', $payload); }
    public static function reservationSnapshotBuilt(array $payload = []): self { return new self('ReservationSnapshotBuilt', $payload); }
}

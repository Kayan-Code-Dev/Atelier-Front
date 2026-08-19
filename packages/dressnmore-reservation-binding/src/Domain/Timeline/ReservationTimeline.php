<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Domain\Timeline;

final class ReservationTimeline
{
    /**
     * @param list<TimelineEntry> $entries
     */
    public function __construct(
        private readonly string $reservationId,
        private readonly string $tenantId,
        private readonly array $entries,
    ) {}

    public function reservationId(): string { return $this->reservationId; }
    public function tenantId(): string { return $this->tenantId; }
    /** @return list<TimelineEntry> */
    public function entries(): array { return $this->entries; }
    public function count(): int { return count($this->entries); }
}

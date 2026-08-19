<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Domain\Timeline;

final class TimelineEntry
{
    /**
     * @param array<string, scalar|null> $metadata
     */
    public function __construct(
        private readonly TimelineKind $kind,
        private readonly string $occurredAt,
        private readonly string $title,
        private readonly ?string $detail = null,
        private readonly array $metadata = [],
    ) {}

    public function kind(): TimelineKind { return $this->kind; }
    public function occurredAt(): string { return $this->occurredAt; }
    public function title(): string { return $this->title; }
    public function detail(): ?string { return $this->detail; }
    /** @return array<string, scalar|null> */
    public function metadata(): array { return $this->metadata; }
}

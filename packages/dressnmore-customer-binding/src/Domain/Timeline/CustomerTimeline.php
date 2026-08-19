<?php

declare(strict_types=1);

namespace DressnMore\CustomerBinding\Domain\Timeline;

final class CustomerTimeline
{
    /**
     * @param list<TimelineEntry> $entries
     */
    public function __construct(
        private readonly string $customerId,
        private readonly string $tenantId,
        private readonly array $entries,
    ) {}

    public function customerId(): string { return $this->customerId; }
    public function tenantId(): string { return $this->tenantId; }
    /** @return list<TimelineEntry> */
    public function entries(): array { return $this->entries; }

    public function count(): int
    {
        return count($this->entries);
    }
}

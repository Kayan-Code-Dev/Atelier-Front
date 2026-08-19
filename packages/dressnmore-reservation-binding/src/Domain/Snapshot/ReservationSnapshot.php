<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Domain\Snapshot;

use DressnMore\ReservationBinding\Domain\Reservation\ReservationId;

/**
 * Compact snapshot for Planner, Memory, Workflow, Knowledge, Prompt Engine.
 */
final class ReservationSnapshot
{
    /**
     * @param array<string, scalar|null> $highlights
     */
    public function __construct(
        private readonly ReservationId $reservationId,
        private readonly string $tenantId,
        private readonly string $customerDisplayName,
        private readonly string $serviceName,
        private readonly string $date,
        private readonly string $time,
        private readonly string $status,
        private readonly ?string $assignedEmployeeName,
        private readonly int $reminderCount,
        private readonly ?string $summary,
        private readonly array $highlights = [],
    ) {}

    public function reservationId(): ReservationId { return $this->reservationId; }
    public function tenantId(): string { return $this->tenantId; }
    public function customerDisplayName(): string { return $this->customerDisplayName; }
    public function serviceName(): string { return $this->serviceName; }
    public function date(): string { return $this->date; }
    public function time(): string { return $this->time; }
    public function status(): string { return $this->status; }
    public function assignedEmployeeName(): ?string { return $this->assignedEmployeeName; }
    public function reminderCount(): int { return $this->reminderCount; }
    public function summary(): ?string { return $this->summary; }
    /** @return array<string, scalar|null> */
    public function highlights(): array { return $this->highlights; }
}

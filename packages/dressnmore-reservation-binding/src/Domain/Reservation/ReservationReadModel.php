<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Domain\Reservation;

/**
 * AI-safe reservation read model (binding DTO) — not an Eloquent model.
 */
final class ReservationReadModel
{
    /**
     * @param list<array{text:string,at?:string}> $notes
     * @param list<array{type:string,at:string,detail?:string}> $history
     * @param list<array{channel?:string,at:string,status?:string}> $reminders
     * @param list<array<string, scalar|null>> $timelineSeed
     */
    public function __construct(
        private readonly ReservationId $id,
        private readonly string $tenantId,
        private readonly string $customerRef,
        private readonly string $customerDisplayName,
        private readonly string $serviceRef,
        private readonly string $serviceName,
        private readonly string $date,
        private readonly string $time,
        private readonly ?string $assignedEmployeeRef = null,
        private readonly ?string $assignedEmployeeName = null,
        private readonly string $status = 'pending',
        private readonly array $notes = [],
        private readonly array $history = [],
        private readonly array $reminders = [],
        private readonly array $timelineSeed = [],
    ) {}

    public function id(): ReservationId { return $this->id; }
    public function tenantId(): string { return $this->tenantId; }
    public function customerRef(): string { return $this->customerRef; }
    public function customerDisplayName(): string { return $this->customerDisplayName; }
    public function serviceRef(): string { return $this->serviceRef; }
    public function serviceName(): string { return $this->serviceName; }
    public function date(): string { return $this->date; }
    public function time(): string { return $this->time; }
    public function assignedEmployeeRef(): ?string { return $this->assignedEmployeeRef; }
    public function assignedEmployeeName(): ?string { return $this->assignedEmployeeName; }
    public function status(): string { return $this->status; }
    /** @return list<array{text:string,at?:string}> */
    public function notes(): array { return $this->notes; }
    /** @return list<array{type:string,at:string,detail?:string}> */
    public function history(): array { return $this->history; }
    /** @return list<array{channel?:string,at:string,status?:string}> */
    public function reminders(): array { return $this->reminders; }
    /** @return list<array<string, scalar|null>> */
    public function timelineSeed(): array { return $this->timelineSeed; }
}

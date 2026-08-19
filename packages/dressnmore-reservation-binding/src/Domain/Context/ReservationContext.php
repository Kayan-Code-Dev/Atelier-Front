<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Domain\Context;

use DressnMore\ReservationBinding\Domain\Reservation\ReservationId;

/**
 * Rich reservation context for Planner / Prompt / Memory consumers.
 */
final class ReservationContext
{
    /**
     * @param array<string, scalar|null> $reservation
     * @param array<string, scalar|null> $customer
     * @param array<string, scalar|null> $service
     * @param array<string, scalar|null> $assignedEmployee
     * @param list<array{text:string,at?:string}> $notes
     * @param list<array{type:string,at:string,detail?:string}> $history
     * @param list<array{channel?:string,at:string,status?:string}> $reminders
     */
    public function __construct(
        private readonly ReservationId $reservationId,
        private readonly string $tenantId,
        private readonly array $reservation,
        private readonly array $customer,
        private readonly array $service,
        private readonly string $date,
        private readonly string $time,
        private readonly array $assignedEmployee,
        private readonly string $status,
        private readonly array $notes,
        private readonly array $history,
        private readonly array $reminders,
    ) {}

    public function reservationId(): ReservationId { return $this->reservationId; }
    public function tenantId(): string { return $this->tenantId; }
    /** @return array<string, scalar|null> */
    public function reservation(): array { return $this->reservation; }
    /** @return array<string, scalar|null> */
    public function customer(): array { return $this->customer; }
    /** @return array<string, scalar|null> */
    public function service(): array { return $this->service; }
    public function date(): string { return $this->date; }
    public function time(): string { return $this->time; }
    /** @return array<string, scalar|null> */
    public function assignedEmployee(): array { return $this->assignedEmployee; }
    public function status(): string { return $this->status; }
    /** @return list<array{text:string,at?:string}> */
    public function notes(): array { return $this->notes; }
    /** @return list<array{type:string,at:string,detail?:string}> */
    public function history(): array { return $this->history; }
    /** @return list<array{channel?:string,at:string,status?:string}> */
    public function reminders(): array { return $this->reminders; }
}

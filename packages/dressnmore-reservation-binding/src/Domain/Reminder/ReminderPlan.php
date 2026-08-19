<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Domain\Reminder;

final class ReminderPlan
{
    /**
     * @param list<array{channel:string,at:string,message?:string}> $items
     */
    public function __construct(
        private readonly string $reservationId,
        private readonly string $tenantId,
        private readonly array $items,
    ) {}

    public function reservationId(): string { return $this->reservationId; }
    public function tenantId(): string { return $this->tenantId; }
    /** @return list<array{channel:string,at:string,message?:string}> */
    public function items(): array { return $this->items; }
    public function count(): int { return count($this->items); }
}

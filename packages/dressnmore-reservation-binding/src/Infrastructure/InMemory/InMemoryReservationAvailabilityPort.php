<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Infrastructure\InMemory;

use DressnMore\ReservationBinding\Contracts\ReservationAvailabilityPortInterface;
use DressnMore\ReservationBinding\Domain\Availability\AvailabilityResult;
use DressnMore\ReservationBinding\Domain\Availability\AvailabilitySlot;

/**
 * Test/demo availability port only — not DressnMore scheduling engine.
 */
final class InMemoryReservationAvailabilityPort implements ReservationAvailabilityPortInterface
{
    /** @var list<AvailabilitySlot> */
    private array $slots = [];

    /** @var list<string> */
    private array $blocked = [];

    public function seedSlot(AvailabilitySlot $slot): void
    {
        $this->slots[] = $slot;
    }

    public function block(string $tenantId, string $serviceRef, string $date, string $time): void
    {
        $this->blocked[] = $this->key($tenantId, $serviceRef, $date, $time);
    }

    public function check(
        string $tenantId,
        string $serviceRef,
        string $date,
        ?string $time = null,
        ?string $employeeRef = null,
    ): AvailabilityResult {
        if ($time !== null && in_array($this->key($tenantId, $serviceRef, $date, $time), $this->blocked, true)) {
            return new AvailabilityResult(false, [], ['slot_blocked']);
        }

        $matching = array_values(array_filter(
            $this->slots,
            static function (AvailabilitySlot $slot) use ($serviceRef, $date, $time, $employeeRef): bool {
                if ($slot->serviceRef() !== $serviceRef || $slot->date() !== $date || ! $slot->available()) {
                    return false;
                }
                if ($time !== null && $slot->time() !== $time) {
                    return false;
                }
                if ($employeeRef !== null && $slot->employeeRef() !== null && $slot->employeeRef() !== $employeeRef) {
                    return false;
                }

                return true;
            },
        ));

        if ($time !== null && $matching === []) {
            return new AvailabilityResult(false, [], ['no_matching_slot']);
        }

        return new AvailabilityResult($matching !== [] || $time === null, $matching);
    }

    public function slots(
        string $tenantId,
        string $serviceRef,
        string $dateFrom,
        ?string $dateTo = null,
        ?string $employeeRef = null,
    ): array {
        $to = $dateTo ?? $dateFrom;

        return array_values(array_filter(
            $this->slots,
            function (AvailabilitySlot $slot) use ($tenantId, $serviceRef, $dateFrom, $to, $employeeRef): bool {
                if ($slot->serviceRef() !== $serviceRef || ! $slot->available()) {
                    return false;
                }
                if ($slot->date() < $dateFrom || $slot->date() > $to) {
                    return false;
                }
                if ($employeeRef !== null && $slot->employeeRef() !== null && $slot->employeeRef() !== $employeeRef) {
                    return false;
                }
                if (in_array($this->key($tenantId, $serviceRef, $slot->date(), $slot->time()), $this->blocked, true)) {
                    return false;
                }

                return true;
            },
        ));
    }

    private function key(string $tenantId, string $serviceRef, string $date, string $time): string
    {
        return $tenantId.'|'.$serviceRef.'|'.$date.'|'.$time;
    }
}

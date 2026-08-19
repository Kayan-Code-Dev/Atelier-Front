<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Module;

use DressnMore\Aos\Core\Module\AbstractModule;
use DressnMore\ReservationBinding\Application\ReservationToolAdapter;

final class ReservationBindingModule extends AbstractModule
{
    public function __construct(private readonly ReservationToolAdapter $adapter) {}

    public function name(): string
    {
        return $this->assertName('dressnmore.reservation.binding');
    }

    public function title(): string
    {
        return 'DressnMore Reservation Domain Binding';
    }

    public function version(): string
    {
        return '0.15.0';
    }

    public function isHealthy(): bool
    {
        return $this->adapter->supports('CheckAvailability')
            && $this->adapter->supports('CreateReservation');
    }
}

<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Application;

use DressnMore\ReservationBinding\Contracts\ReservationIntentMapperInterface;
use DressnMore\ReservationBinding\Domain\Tools\ReservationToolName;

final class ReservationIntentMapper implements ReservationIntentMapperInterface
{
    public function map(string $intent): ?string
    {
        $key = strtolower(trim($intent));

        return match ($key) {
            'check_availability', 'is_available' => ReservationToolName::CheckAvailability->value,
            'available_slots', 'get_slots' => ReservationToolName::GetAvailableSlots->value,
            'create_reservation', 'book', 'new_booking' => ReservationToolName::CreateReservation->value,
            'update_reservation' => ReservationToolName::UpdateReservation->value,
            'cancel_reservation', 'cancel_booking' => ReservationToolName::CancelReservation->value,
            'reschedule_reservation', 'reschedule' => ReservationToolName::RescheduleReservation->value,
            'confirm_reservation', 'confirm_booking' => ReservationToolName::ConfirmReservation->value,
            'get_reservation', 'reservation_details' => ReservationToolName::GetReservation->value,
            'customer_reservations' => ReservationToolName::GetCustomerReservations->value,
            'today_reservations', 'todays_reservations', "today's_reservations" => ReservationToolName::GetTodaysReservations->value,
            'reservation_summary' => ReservationToolName::ReservationSummary->value,
            'reservation_timeline' => ReservationToolName::ReservationTimeline->value,
            default => null,
        };
    }
}

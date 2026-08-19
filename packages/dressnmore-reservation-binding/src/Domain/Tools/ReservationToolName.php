<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Domain\Tools;

enum ReservationToolName: string
{
    case CheckAvailability = 'CheckAvailability';
    case GetAvailableSlots = 'GetAvailableSlots';
    case CreateReservation = 'CreateReservation';
    case UpdateReservation = 'UpdateReservation';
    case CancelReservation = 'CancelReservation';
    case RescheduleReservation = 'RescheduleReservation';
    case ConfirmReservation = 'ConfirmReservation';
    case GetReservation = 'GetReservation';
    case GetCustomerReservations = 'GetCustomerReservations';
    case GetTodaysReservations = "GetToday'sReservations";
    case ReservationSummary = 'ReservationSummary';
    case ReservationTimeline = 'ReservationTimeline';
}

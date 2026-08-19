<?php

declare(strict_types=1);

namespace DressnMore\ReservationBinding\Domain\Tools;

use DressnMore\Aos\Tools\Domain\Tool\ToolRiskLevel;

/**
 * Catalog of Reservation Business Tool contracts (binding layer — no domain execution).
 */
final class ReservationToolCatalog
{
    /**
     * @return list<ReservationToolContract>
     */
    public static function all(): array
    {
        return [
            new ReservationToolContract(
                ReservationToolName::CheckAvailability,
                'Check whether a service slot is available for booking',
                ['tenantId', 'serviceRef', 'date', 'time?', 'employeeRef?'],
                ['available:bool', 'conflicts[]?'],
                ['check_availability', 'read_schedule'],
                'reservation.availability.check',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['ReservationContextBuilt'],
            ),
            new ReservationToolContract(
                ReservationToolName::GetAvailableSlots,
                'List available booking slots for a service/date window',
                ['tenantId', 'serviceRef', 'dateFrom', 'dateTo?', 'employeeRef?'],
                ['slots[]'],
                ['list_available_slots', 'read_schedule'],
                'reservation.slots.read',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['ReservationContextBuilt'],
            ),
            new ReservationToolContract(
                ReservationToolName::CreateReservation,
                'Create a new reservation under tenant booking policy',
                ['tenantId', 'customerRef', 'serviceRef', 'date', 'time', 'employeeRef?', 'notes?'],
                ['reservationRef'],
                ['create_booking'],
                'reservation.create',
                ToolRiskLevel::Medium,
                ApprovalPolicy::Often,
                ['ReservationCreated', 'ReservationReminderScheduled'],
            ),
            new ReservationToolContract(
                ReservationToolName::UpdateReservation,
                'Update mutable reservation fields (notes, employee, service details)',
                ['tenantId', 'reservationRef', 'patch fields'],
                ['updated reservation'],
                ['update_booking'],
                'reservation.update',
                ToolRiskLevel::Medium,
                ApprovalPolicy::Often,
                ['ReservationUpdated'],
            ),
            new ReservationToolContract(
                ReservationToolName::CancelReservation,
                'Cancel an existing reservation with optional reason',
                ['tenantId', 'reservationRef', 'reason?'],
                ['cancelled reservation'],
                ['cancel_booking'],
                'reservation.cancel',
                ToolRiskLevel::Medium,
                ApprovalPolicy::Often,
                ['ReservationCancelled'],
            ),
            new ReservationToolContract(
                ReservationToolName::RescheduleReservation,
                'Move a reservation to a new date/time/slot',
                ['tenantId', 'reservationRef', 'newDate', 'newTime', 'employeeRef?'],
                ['rescheduled reservation'],
                ['reschedule_booking'],
                'reservation.reschedule',
                ToolRiskLevel::Medium,
                ApprovalPolicy::Often,
                ['ReservationRescheduled', 'ReservationUpdated', 'ReservationReminderScheduled'],
            ),
            new ReservationToolContract(
                ReservationToolName::ConfirmReservation,
                'Confirm a pending reservation',
                ['tenantId', 'reservationRef'],
                ['confirmed reservation'],
                ['confirm_booking'],
                'reservation.confirm',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['ReservationConfirmed', 'ReservationUpdated'],
            ),
            new ReservationToolContract(
                ReservationToolName::GetReservation,
                'Resolve and return a single reservation safe for AI consumption',
                ['tenantId', 'reservationRef'],
                ['reservation context/DTO'],
                ['read_schedule', 'read_reservation'],
                'reservation.read',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['ReservationContextBuilt'],
            ),
            new ReservationToolContract(
                ReservationToolName::GetCustomerReservations,
                'List reservations linked to a customer',
                ['tenantId', 'customerRef', 'status?', 'window?'],
                ['reservations[]'],
                ['read_schedule', 'list_customer_reservations'],
                'reservation.customer.list',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['ReservationContextBuilt'],
            ),
            new ReservationToolContract(
                ReservationToolName::GetTodaysReservations,
                "List today's reservations for the tenant (reception board)",
                ['tenantId', 'date?', 'employeeRef?', 'status?'],
                ['reservations[]'],
                ['read_schedule', 'list_today_reservations'],
                'reservation.today.list',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['ReservationSnapshotBuilt'],
            ),
            new ReservationToolContract(
                ReservationToolName::ReservationSummary,
                'Produce an AI-facing reservation summary snapshot',
                ['tenantId', 'reservationRef'],
                ['summary text/structure'],
                ['read_reservation_summary'],
                'reservation.summary.read',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['ReservationSnapshotBuilt'],
            ),
            new ReservationToolContract(
                ReservationToolName::ReservationTimeline,
                'Build reservation lifecycle timeline for AI/workspace',
                ['tenantId', 'reservationRef'],
                ['timeline entries[]'],
                ['read_reservation_timeline'],
                'reservation.timeline.read',
                ToolRiskLevel::Low,
                ApprovalPolicy::None,
                ['ReservationContextBuilt'],
            ),
        ];
    }

    public static function get(ReservationToolName $name): ReservationToolContract
    {
        foreach (self::all() as $contract) {
            if ($contract->name() === $name) {
                return $contract;
            }
        }

        throw new \InvalidArgumentException('Unknown reservation tool: '.$name->value);
    }
}

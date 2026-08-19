# Reservation Domain → AI Business Tools Mapping

**Sprint 15** mapping document.

## Purpose

Explain how DressnMore Reservation Domain concepts become AOS-consumable Business Tools and context projections.

## Domain concept → Binding artifact

| Reservation Domain concept | Binding artifact | AI consumer |
|----------------------------|------------------|-------------|
| Reservation aggregate | `ReservationId` + `ReservationReadModel` | Tools / Context |
| Slot availability | `AvailabilityPort` + `AvailabilityResolver` | CheckAvailability / GetAvailableSlots |
| Booking mutations | Create/Update/Cancel/Reschedule/Confirm contracts | Planner + HITL |
| Customer linkage | Context.customer + GetCustomerReservations | Reception intents |
| Day board | GetToday'sReservations | Workspace / Prompt |
| Reminders | `ReminderPlan` + ReminderScheduled event | Workflow / Communication |
| Lifecycle audit | `ReservationTimeline` | Workspace / Memory |

## Tool mapping to Integration Review catalog

| Sprint 15 Tool | Closest AOS catalog name | Notes |
|----------------|--------------------------|-------|
| CheckAvailability | (derived) | Pre-create guard |
| GetAvailableSlots | (derived) | Slot listing |
| CreateReservation | CreateReservation | Same |
| UpdateReservation | (derived) | Notes/employee patch |
| CancelReservation | CancelReservation | Same |
| RescheduleReservation | RescheduleReservation | Same |
| ConfirmReservation | (derived) | Pending → confirmed |
| GetReservation | GetReservation | Same |
| GetCustomerReservations | ListReservationsForCustomer | Alias |
| GetToday'sReservations | (derived) | Reception board |
| ReservationSummary | (derived) | Snapshot summary |
| ReservationTimeline | (derived) | Lifecycle projection |

## Event mapping

| Binding event | When | Downstream |
|---------------|------|------------|
| ReservationCreated / Updated / Cancelled / Confirmed / Rescheduled | Future write adapters | Audit + Memory + Workflow |
| ReservationReminderScheduled | Reminder builder | Communication Hub |
| ReservationContextBuilt / SnapshotBuilt | Builders | Prompt / Planner / Memory |

## Non-goals

No Controllers, Routes, DB, Eloquent, Queries, HTTP, live Tool execution against DressnMore services.

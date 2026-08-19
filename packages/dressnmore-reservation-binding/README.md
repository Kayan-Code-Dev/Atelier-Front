# DressnMore Reservation Domain Binding (`dressnmore/reservation-binding`)

**Sprint 15** — Reservation Domain ↔ AOS Business Capabilities (contracts-first).

## Binding Philosophy

This package does **not** expose Eloquent, Controllers, or HTTP.  
It translates the Reservation bounded context into **AOS Business Tool contracts** and AI-safe projections (Context / Snapshot / Timeline / Reminders) so the Digital Employee can act as a professional receptionist without knowing DressnMore internals.

```
DressnMore Reservation Domain
        ↓ (future adapters behind ports)
Read Model Port + Availability Port
        ↓
Resolver / Context / Snapshot / Timeline / Reminder Builders
        ↓
Reservation Tool Contracts
        ↓
AOS Tool Gateway + Planner + Prompt + Memory + Workflow
```

## Architecture

Hexagonal + Contracts First:

| Layer | Contents |
|-------|----------|
| Contracts | Ports for availability, builders, tool adapter, capabilities, policies, events |
| Domain | Tool catalog, read model DTO, context/snapshot/timeline/reminder, events |
| Application | Pure binding composers + catalog adapters |
| Infrastructure | In-memory ports for tests/demo only |

Excluded: Controllers, Routes, Database, Laravel Models, Queries, Domain service implementations, API, HTTP.

## Reservation Context

AI-facing context: Reservation, Customer, Service, Date, Time, Assigned Employee, Status, Notes, History, Reminders.

## Reservation Snapshot

Compact projection for Planner, Memory, Workflow, Knowledge, Prompt Engine.

## Business Tools (12)

CheckAvailability · GetAvailableSlots · CreateReservation · UpdateReservation · CancelReservation · RescheduleReservation · ConfirmReservation · GetReservation · GetCustomerReservations · GetToday'sReservations · ReservationSummary · ReservationTimeline

Each contract defines Purpose, Inputs, Outputs, Capabilities, Permission, Risk, Approval Policy, Expected Events.

Mutating tools (create/update/cancel/reschedule) use **Medium** risk + **Often** approval.

## Timeline

Lifecycle kinds: Creation, Updates, Reschedule, Cancellation, Reminder, Arrival, Completion.

## Extension Points

1. Replace `ReservationReadModelPortInterface` / `ReservationAvailabilityPortInterface` with DressnMore adapters.  
2. Register tool contracts into AOS Tool Gateway handlers.  
3. Bridge `ReservationEventPublisherInterface` to `aos-events`.  
4. Wire `ReservationReminderBuilder` into Workflow / Communication Hub for delivery.  

## Module

- Provider: `ReservationBindingServiceProvider`
- Module: `dressnmore.reservation.binding`
- Mapping: `docs/RESERVATION-DOMAIN-MAPPING.md`

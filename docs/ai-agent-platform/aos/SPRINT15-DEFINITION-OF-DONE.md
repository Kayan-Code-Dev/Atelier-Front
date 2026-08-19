# Sprint 15 — Definition of Done (Reservation Domain Binding)

## Delivered

- Package: `packages/dressnmore-reservation-binding`
- Ports: Tool Adapter, Availability Resolver, Context/Snapshot/Timeline/Reminder builders, Capability Provider, Intent Mapper, Policy Adapter, Event Publisher, Read Model + Availability ports
- 12 Reservation Business Tool contracts
- Reservation Context / Snapshot / Timeline / Reminder models
- Domain events for create/update/cancel/confirm/reschedule/reminder/context/snapshot
- Mapping document + README
- PHPUnit coverage for availability, context, snapshot, timeline, contracts
- Module registration: `dressnmore.reservation.binding`

## Explicit non-goals

Controllers · Routes · Database · Laravel Models · Queries · Repository implementations · Domain service implementations · APIs · HTTP

## Validation

```bash
# after composer update dressnmore/reservation-binding
vendor/bin/phpunit packages/dressnmore-reservation-binding/tests
```

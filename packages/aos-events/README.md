# dressnmore/aos-events

## Purpose

Foundation eventing for AOS: bus, dispatcher/publisher contracts, subscriber contracts, and domain/application/infrastructure event markers.

## Responsibilities

- `EventBusInterface` / `PublisherInterface` / `DispatcherInterface`
- `SubscriberInterface` / `EventListenerInterface`
- `AbstractEvent` base
- Markers: `DomainEventMarker`, `ApplicationEventMarker`, `InfrastructureEventMarker`
- Laravel dispatcher adapter
- `aos.events` module registration

## Dependencies

- `dressnmore/aos-core` (module contracts only)
- `illuminate/events`

## Extension points

- Publish domain events via `EventBusInterface::publish()`
- Implement `SubscriberInterface` and register with `EventBus::registerSubscriber()`
- Mark events with the appropriate marker interface

## Out of scope

No business domain events are defined in Sprint 1 (only `EventBusReady` infrastructure marker event).

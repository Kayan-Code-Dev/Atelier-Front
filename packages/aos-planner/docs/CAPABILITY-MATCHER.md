# Capability Matcher

`CapabilityMatcher` links a resolved intent to required capabilities.

## Example

```
BookReservation
  → Reservation.Read
  → Reservation.Create
```

## Modes

- `registeredCapabilities = null` — required list treated as matched (catalog trust)
- Explicit list — missing codes → `CapabilityMatch::ok() === false`
- `*` wildcard grants all

Missing capabilities reject the plan before Gateway.

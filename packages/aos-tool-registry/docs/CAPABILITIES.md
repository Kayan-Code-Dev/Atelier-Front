# Capabilities

Capabilities are domain-owned ability tokens, independent of tool names.

## Naming convention

`{Domain}.{Action}` — e.g. `Customer.Read`, `Reservation.Create`.

## Bootstrap examples

| Capability | Domain | Write |
|------------|--------|-------|
| Customer.Read | customer | no |
| Customer.Write | customer | yes |
| Customer.Search | customer | no |
| Reservation.Read | reservation | no |
| Reservation.Create | reservation | yes |
| Reservation.Update | reservation | yes |

## Validation

`CapabilityValidator::assertGranted(required, granted)`:

- Capability not in registry → Permission Denied
- Capability not in granted set → Permission Denied

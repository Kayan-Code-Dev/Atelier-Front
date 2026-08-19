# Intent Mapping

Intent → ordered Business Tools → Capabilities → Policy → Approval

## Examples

### BookReservation

```
BookReservation
  → CheckAvailability   (Reservation.Read)
  → CreateReservation   (Reservation.Create)
Policy: booking_write_policy
Approval: often
```

### CancelReservation

```
CancelReservation
  → CancelReservation   (Reservation.Update)
Policy: booking_write_policy
Approval: often
```

### CreateCustomer

```
CreateCustomer
  → SearchCustomer      (Customer.Search)
  → CreateCustomer      (Customer.Write)
Policy: default_read_policy
Approval: often
```

Planner resolves the intent, then walks the tool plan via ToolResolver without importing domain packages.

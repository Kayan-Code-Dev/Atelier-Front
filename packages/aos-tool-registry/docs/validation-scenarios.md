# Validation Scenarios

## 1. Customer Module registers itself

```
Customer Module → ToolRegistrar
  → capabilities Customer.*
  → tools GetCustomer, SearchCustomer, CreateCustomer
  → intent CreateCustomer
Planner → ToolDiscovery(category=customer)
Tool Gateway consumer → ToolResolver('GetCustomer') → Descriptor
```

**Expected:** tools discoverable; descriptor has ownerDomain=`customer`.

## 2. Reservation Module registers itself

```
Reservation Module → ToolRegistrar
  → Reservation.Read / Create / Update
  → CheckAvailability, CreateReservation, CancelReservation
  → intents BookReservation, CancelReservation
Planner discovers reservation tools
CapabilityValidator asserts Reservation.Create when booking
```

**Expected:** BookReservation plan resolves to CheckAvailability → CreateReservation.

## 3. Unregistered tool

```
Planner → ToolResolver('TotallyUnknownTool')
```

**Expected:** rejection / `ToolDiscoveryRejected` — execution refused.

## 4. Missing capability

```
CapabilityValidator.assertGranted(['Reservation.Create'], ['Customer.Read'])
```

**Expected:** Permission Denied (`CapabilityDenied`).

## 5. Incompatible tool version

```
Registered GetCustomer @ 1.0.0
Resolver.resolve('GetCustomer', minimum=2.0.0)
```

**Expected:** registry blocks use (`ToolVersionIncompatible`).

# Contracts — Catalog, Inventory, Measurements

---

## SearchProducts

| Field | Content |
|-------|---------|
| Tool Name | `SearchProducts` |
| Business Purpose | Find products/dresses by attributes |
| Description | Catalog search returning displayable candidates |
| Business Intent(s) | AskAvailability, RentDress, AskPrice |
| Required Capabilities | `read_catalog_availability` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | Isolation Key |
| Expected Inputs | query; filters (occasion, color, size)?; limit |
| Expected Output | products[{productRef, title, attrs}] |
| Possible Outcomes | Matches / Empty |
| Failure Scenarios | Invalid filters |
| Validation Rules | Limit; allowed filter keys |
| Approval Requirements | None |
| Human Escalation Rules | Empty + high intent → SuggestProducts/staff |
| Audit Events | ToolExecuted |
| Analytics Events | ProductSearch |
| Business Rules | Only sellable/visible items |
| Security Considerations | No internal cost |
| Idempotency Rules | Read |
| Concurrency Considerations | — |
| Side Effects | None |
| Dependencies | Catalog read |
| Related Tools | SuggestProducts, CheckItemAvailability |
| Versioning Notes | v1 |

---

## SuggestProducts

| Field | Content |
|-------|---------|
| Tool Name | `SuggestProducts` |
| Business Purpose | Recommend items from preferences / similar |
| Description | Suggestion ranking over catalog |
| Business Intent(s) | RentDress, AskAvailability, SendImage |
| Required Capabilities | `read_catalog_availability` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | Memory prefs; optional media match hints |
| Expected Inputs | preferences; excludeRefs?; limit |
| Expected Output | ranked products[] |
| Possible Outcomes | Suggestions / Empty |
| Failure Scenarios | Insufficient prefs |
| Validation Rules | Limit |
| Approval Requirements | None |
| Human Escalation Rules | Styling consult optional |
| Audit Events | ToolExecuted |
| Analytics Events | ProductSuggested |
| Business Rules | Suggestions ≠ availability guarantee |
| Security Considerations | — |
| Idempotency Rules | Read (non-deterministic ranking allowed) |
| Concurrency Considerations | — |
| Side Effects | None |
| Dependencies | SearchProducts / catalog |
| Related Tools | CheckItemAvailability |
| Versioning Notes | v1 |

---

## ResolveProduct

| Field | Content |
|-------|---------|
| Tool Name | `ResolveProduct` |
| Business Purpose | Map customer wording/code to productRef |
| Description | Resolution helper before availability |
| Business Intent(s) | CheckItemAvailability |
| Required Capabilities | `read_catalog_availability` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | utterance / code |
| Expected Inputs | textOrCode |
| Expected Output | productRef or candidates |
| Possible Outcomes | Resolved / Ambiguous / NotFound |
| Failure Scenarios | — |
| Validation Rules | Non-empty input |
| Approval Requirements | None |
| Human Escalation Rules | Persistent Ambiguous |
| Audit Events | ToolExecuted |
| Analytics Events | ProductResolved |
| Business Rules | Clarify if Ambiguous |
| Security Considerations | — |
| Idempotency Rules | Read |
| Concurrency Considerations | — |
| Side Effects | None |
| Dependencies | Catalog |
| Related Tools | CheckItemAvailability |
| Versioning Notes | v1 |

---

## SearchAvailability / SearchInventory

| Field | Content |
|-------|---------|
| Tool Name | `SearchAvailability` (inventory-oriented alias `SearchInventory`) |
| Business Purpose | Find what is available for a date/need |
| Description | Availability search across inventory |
| Business Intent(s) | AskAvailability, RentDress |
| Required Capabilities | `read_catalog_availability` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | date if rental; filters |
| Expected Inputs | dateRange?; filters; limit |
| Expected Output | available items[] |
| Possible Outcomes | Matches / None |
| Failure Scenarios | Tool timeout |
| Validation Rules | Date order |
| Approval Requirements | None |
| Human Escalation Rules | None persistent → waitlist Task |
| Audit Events | ToolExecuted |
| Analytics Events | AvailabilitySearch |
| Business Rules | Available now ≠ reserved |
| Security Considerations | — |
| Idempotency Rules | Read (time-varying) |
| Concurrency Considerations | Soft state until hold |
| Side Effects | None |
| Dependencies | Inventory read |
| Related Tools | CheckItemAvailability, CreateRentalHold |
| Versioning Notes | v1 |

---

## CheckItemAvailability

| Field | Content |
|-------|---------|
| Tool Name | `CheckItemAvailability` |
| Business Purpose | Confirm a specific item for a date |
| Description | Boolean/status availability for productRef+date |
| Business Intent(s) | CheckItemAvailability |
| Required Capabilities | `read_catalog_availability` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | productRef; date |
| Expected Inputs | productRef; date; branch? |
| Expected Output | available; conflictsHint? |
| Possible Outcomes | Available / Unavailable / Unknown |
| Failure Scenarios | Bad productRef |
| Validation Rules | Future date for rental |
| Approval Requirements | None |
| Human Escalation Rules | Customer demands hold without capability |
| Audit Events | ToolExecuted |
| Analytics Events | ItemAvailabilityCheck |
| Business Rules | Unknown → Clarify/Escalate not guess |
| Security Considerations | — |
| Idempotency Rules | Read |
| Concurrency Considerations | Race until hold |
| Side Effects | None |
| Dependencies | ResolveProduct |
| Related Tools | CreateRentalHold |
| Versioning Notes | v1 |

---

## CreateRentalHold

| Field | Content |
|-------|---------|
| Tool Name | `CreateRentalHold` |
| Business Purpose | Temporarily hold item for customer |
| Description | Soft reservation on inventory |
| Business Intent(s) | RentDress |
| Required Capabilities | `create_rental_hold` |
| Required Permissions | Policy grant |
| Allowed Modes | A Approval · H often Approval · F if allowed |
| Required Context | customer; product; date; High conf |
| Expected Inputs | productRef; customerRef; date; ttl?; idempotencyKey |
| Expected Output | holdRef; expiresAt |
| Possible Outcomes | Held / Unavailable / PendingApproval / Denied |
| Failure Scenarios | Race lost |
| Validation Rules | Item available; TTL bounds |
| Approval Requirements | Often Yes |
| Human Escalation Rules | High-value items |
| Audit Events | ToolExecuted, Approval* |
| Analytics Events | RentalHoldCreated |
| Business Rules | Auto-expire; no payment assumption |
| Security Considerations | — |
| Idempotency Rules | Required |
| Concurrency Considerations | Conditional hold |
| Side Effects | Inventory hold |
| Dependencies | CheckItemAvailability |
| Related Tools | CreateReservation, NotifyCustomer |
| Versioning Notes | v1 |

---

## GetPublishedPricing

| Field | Content |
|-------|---------|
| Tool Name | `GetPublishedPricing` |
| Business Purpose | Return published price ranges/packages |
| Description | Pricing read from published sources |
| Business Intent(s) | AskPrice |
| Required Capabilities | `read_pricing` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | service/product type |
| Expected Inputs | serviceType or productRef |
| Expected Output | priceBands / packages |
| Possible Outcomes | Found / NotPublished |
| Failure Scenarios | Missing config |
| Validation Rules | — |
| Approval Requirements | None |
| Human Escalation Rules | NotPublished → Sales |
| Audit Events | ToolExecuted |
| Analytics Events | PricingRead |
| Business Rules | No invented prices |
| Security Considerations | No dealer costs |
| Idempotency Rules | Read |
| Concurrency Considerations | — |
| Side Effects | None |
| Dependencies | Pricing/KB publish |
| Related Tools | ListPublishedOffers, GenerateQuotation |
| Versioning Notes | v1 |

---

## ListPublishedOffers

| Field | Content |
|-------|---------|
| Tool Name | `ListPublishedOffers` |
| Business Purpose | List active seasonal/public offers |
| Description | Offers currently valid |
| Business Intent(s) | AskSeasonalOffers, RequestDiscount |
| Required Capabilities | `read_offers` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | asOf date |
| Expected Inputs | optional category |
| Expected Output | offers[] with validity |
| Possible Outcomes | List / Empty |
| Failure Scenarios | — |
| Validation Rules | Only active |
| Approval Requirements | None |
| Human Escalation Rules | Custom discount beyond list |
| Audit Events | ToolExecuted |
| Analytics Events | OffersListed |
| Business Rules | Expired excluded |
| Security Considerations | — |
| Idempotency Rules | Read |
| Concurrency Considerations | — |
| Side Effects | None |
| Dependencies | Offers publish |
| Related Tools | ApplyDiscount (critical) |
| Versioning Notes | v1 |

---

## GetServiceLeadTimes

| Field | Content |
|-------|---------|
| Tool Name | `GetServiceLeadTimes` |
| Business Purpose | Explain minimum lead time for tailoring/services |
| Description | Reads lead-time policy |
| Business Intent(s) | RequestTailoring, BookAppointment |
| Required Capabilities | `read_knowledge` or `read_atelier_settings` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | serviceType |
| Expected Inputs | serviceType |
| Expected Output | minDays; notes |
| Possible Outcomes | Found / Unknown |
| Failure Scenarios | Missing |
| Validation Rules | — |
| Approval Requirements | None |
| Human Escalation Rules | Rush requests |
| Audit Events | ToolExecuted |
| Analytics Events | LeadTimeRead |
| Business Rules | Rush = Escalation not auto-promise |
| Security Considerations | — |
| Idempotency Rules | Read |
| Concurrency Considerations | — |
| Side Effects | None |
| Dependencies | Settings/KB |
| Related Tools | CreateReservation |
| Versioning Notes | v1 |

---

## GetMeasurements / SaveMeasurements

| Field | Content |
|-------|---------|
| Tool Name | `GetMeasurements` / `SaveMeasurements` |
| Business Purpose | Read or store customer measurements |
| Description | Measurement profile for rental/tailoring |
| Business Intent(s) | AskSizeGuide, ProvideCustomerData, RequestTailoring |
| Required Capabilities | `read_measurements` / `write_measurements` |
| Required Permissions | Policy grant |
| Allowed Modes | Read: A,H,F · Write: A Approval?, H, F if granted |
| Required Context | customerRef |
| Expected Inputs | Get: customerRef · Save: customerRef; measurementMap; idempotencyKey? |
| Expected Output | measurementMap / saved |
| Possible Outcomes | Found/Empty · Saved/Denied |
| Failure Scenarios | Invalid units |
| Validation Rules | Allowed keys; ranges |
| Approval Requirements | Write optional |
| Human Escalation Rules | Unusual bridal fitting |
| Audit Events | ToolExecuted |
| Analytics Events | MeasurementsRead/Saved |
| Business Rules | Does not auto-alter orders |
| Security Considerations | Sensitive body data minimization |
| Idempotency Rules | Save recommended |
| Concurrency Considerations | Last-write-wins with audit |
| Side Effects | Write persists measurements |
| Dependencies | Customer profile |
| Related Tools | UpsertCustomerProfile |
| Versioning Notes | v1 |

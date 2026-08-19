# Contracts — Reservation, Order, Delivery

---

## FindAvailableSlots

| Field | Content |
|-------|---------|
| Tool Name | `FindAvailableSlots` |
| Business Purpose | Propose bookable fitting/appointment slots |
| Description | Returns available time slots for a service/branch/date range |
| Business Intent(s) | BookAppointment, RescheduleAppointment, RequestTailoring |
| Required Capabilities | `read_schedule` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | Isolation Key; timezone; service type |
| Expected Inputs | serviceType; branch?; dateRange; duration? |
| Expected Output | slots[{start, end, branch, capacityHint}] |
| Possible Outcomes | SlotsFound / NoneAvailable |
| Failure Scenarios | Invalid range; branch unknown |
| Validation Rules | Range max window; end>start; service allowed |
| Approval Requirements | None |
| Human Escalation Rules | NoneAvailable repeated → optional staff |
| Audit Events | ToolExecuted |
| Analytics Events | SlotsQueried |
| Business Rules | Respect Business Hours; no oversell beyond capacity rules |
| Security Considerations | No staff PII in slots |
| Idempotency Rules | Read-idempotent (slots may change over time) |
| Concurrency Considerations | Results are hints until CreateReservation |
| Side Effects | None |
| Dependencies | Schedule read port |
| Related Tools | CreateReservation, RescheduleReservation |
| Versioning Notes | v1 |

---

## CreateReservation

| Field | Content |
|-------|---------|
| Tool Name | `CreateReservation` |
| Business Purpose | Book a fitting/appointment reservation |
| Description | Creates reservation after slot validation; alias conceptual to CreateAppointment in Use Cases |
| Business Intent(s) | BookAppointment, RentDress, RequestTailoring |
| Required Capabilities | `create_booking` |
| Required Permissions | Policy grant |
| Allowed Modes | A (Approval) · H (often Approval) · F (if granted + High conf) |
| Required Context | CustomerRef or create-customer plan; confirmed slot; Conversation |
| Expected Inputs | customerRef; slotId/start; serviceType; branch?; notes?; idempotencyKey |
| Expected Output | reservationRef; confirmedTime; location |
| Possible Outcomes | Created / Conflict / Denied / PendingApproval |
| Failure Scenarios | Slot taken; customer missing; capability deny |
| Validation Rules | Slot must be free; customer required; explicit customer confirm in Conversation Memory |
| Approval Requirements | Default Yes in Hybrid unless policy auto-allow low-risk |
| Human Escalation Rules | Conflicts; VIP; capability missing |
| Audit Events | Approval*, ToolExecuted |
| Analytics Events | ReservationCreated |
| Business Rules | No double-book same customer overlapping; respect lead time |
| Security Considerations | Tenant isolation; no cross-branch unauthorized |
| Idempotency Rules | **Required** idempotencyKey → same reservation if retry |
| Concurrency Considerations | Conditional create on slot version/token |
| Side Effects | Reservation created; may notify |
| Dependencies | FindAvailableSlots (logical); customer identity |
| Related Tools | FindAvailableSlots, NotifyCustomer, GetReservation |
| Versioning Notes | v1; synonym CreateAppointment accepted in mapping |

---

## GetReservation

| Field | Content |
|-------|---------|
| Tool Name | `GetReservation` |
| Business Purpose | Fetch reservation details for confirm/reschedule/cancel |
| Description | Read single reservation |
| Business Intent(s) | ConfirmAppointment, RescheduleAppointment, CancelAppointment, AppointmentReminder |
| Required Capabilities | `read_schedule` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | Isolation Key; ownership of customer |
| Expected Inputs | reservationRef **or** (customerRef + date hint) |
| Expected Output | status, time, branch, service |
| Possible Outcomes | Found / NotFound / Ambiguous |
| Failure Scenarios | Wrong customer scope |
| Validation Rules | Must belong to tenant; customer scope check |
| Approval Requirements | None |
| Human Escalation Rules | Ambiguous |
| Audit Events | ToolExecuted |
| Analytics Events | ReservationRead |
| Business Rules | Hide internal staff notes if not permitted |
| Security Considerations | IDOR prevention via customer scope |
| Idempotency Rules | Read |
| Concurrency Considerations | Snapshot |
| Side Effects | None |
| Dependencies | Schedule read |
| Related Tools | ListReservationsForCustomer |
| Versioning Notes | v1 |

---

## ListReservationsForCustomer

| Field | Content |
|-------|---------|
| Tool Name | `ListReservationsForCustomer` |
| Business Purpose | List upcoming/past reservations for disambiguation |
| Description | Customer-scoped reservation list |
| Business Intent(s) | ConfirmAppointment, RescheduleAppointment, CancelAppointment |
| Required Capabilities | `read_schedule` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | customerRef |
| Expected Inputs | customerRef; statusFilter?; limit |
| Expected Output | reservations[] |
| Possible Outcomes | List / Empty |
| Failure Scenarios | Missing customer |
| Validation Rules | Limit cap |
| Approval Requirements | None |
| Human Escalation Rules | None |
| Audit Events | ToolExecuted |
| Analytics Events | ReservationList |
| Business Rules | Default upcoming-first |
| Security Considerations | Customer scope only |
| Idempotency Rules | Read |
| Concurrency Considerations | — |
| Side Effects | None |
| Dependencies | Schedule read |
| Related Tools | GetReservation |
| Versioning Notes | v1 |

---

## RescheduleReservation

| Field | Content |
|-------|---------|
| Tool Name | `RescheduleReservation` |
| Business Purpose | Move reservation to a new slot |
| Description | Validates policy window then updates time |
| Business Intent(s) | RescheduleAppointment |
| Required Capabilities | `reschedule_booking` |
| Required Permissions | Policy grant |
| Allowed Modes | A (Approval) · H · F (with rules) |
| Required Context | Existing reservation; new slot; policy |
| Expected Inputs | reservationRef; newSlot; idempotencyKey; customerConfirm |
| Expected Output | updated reservation |
| Possible Outcomes | Rescheduled / PolicyBlocked / Conflict / PendingApproval |
| Failure Scenarios | Inside lock window; slot gone |
| Validation Rules | Policy window; ownership; confirm flag |
| Approval Requirements | Near-term / fee cases Yes |
| Human Escalation Rules | Exceptions to policy |
| Audit Events | ToolExecuted, Approval* |
| Analytics Events | ReservationRescheduled |
| Business Rules | Apply cancellation/reschedule policy from Knowledge facts already in Context |
| Security Considerations | Customer scope |
| Idempotency Rules | Required |
| Concurrency Considerations | Slot token |
| Side Effects | Reservation updated; optional notify |
| Dependencies | FindAvailableSlots, GetReservation |
| Related Tools | CancelReservation |
| Versioning Notes | v1 |

---

## CancelReservation

| Field | Content |
|-------|---------|
| Tool Name | `CancelReservation` |
| Business Purpose | Cancel a reservation under policy |
| Description | Soft-cancel appointment; may imply fees communicated separately |
| Business Intent(s) | CancelAppointment |
| Required Capabilities | `cancel_booking` |
| Required Permissions | Policy grant |
| Allowed Modes | A (Approval) · H (often Approval) · F (Approval typical) |
| Required Context | Reservation; policy; explicit confirm |
| Expected Inputs | reservationRef; reason?; customerConfirm; idempotencyKey |
| Expected Output | cancelled status |
| Possible Outcomes | Cancelled / PolicyBlocked / PendingApproval / Denied |
| Failure Scenarios | Already started; deny capability |
| Validation Rules | Explicit confirm; cancellable status |
| Approval Requirements | Frequently Yes |
| Human Escalation Rules | Fee disputes |
| Audit Events | ToolExecuted, Approval* |
| Analytics Events | ReservationCancelled |
| Business Rules | Does not auto-refund |
| Security Considerations | Scope checks |
| Idempotency Rules | Cancel twice → success no-op |
| Concurrency Considerations | Status transition guard |
| Side Effects | Cancelled; optional NotifyCustomer/Staff |
| Dependencies | GetReservation |
| Related Tools | CreateFollowUp |
| Versioning Notes | v1 |

---

## GetOrderStatus

| Field | Content |
|-------|---------|
| Tool Name | `GetOrderStatus` |
| Business Purpose | Report rental/tailoring/sales order progress |
| Description | Read order status and next milestone |
| Business Intent(s) | TrackOrder |
| Required Capabilities | `read_order_status` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | customer/order identity |
| Expected Inputs | orderRef **or** (customerRef + orderHint) |
| Expected Output | status, type, milestones, ETA hints |
| Possible Outcomes | Found / NotFound / Ambiguous |
| Failure Scenarios | Wrong customer |
| Validation Rules | Tenant + customer scope |
| Approval Requirements | None |
| Human Escalation Rules | Angry delay → complaint path (not this tool) |
| Audit Events | ToolExecuted |
| Analytics Events | OrderStatusRead |
| Business Rules | No fabricated ETAs beyond domain data |
| Security Considerations | IDOR prevention |
| Idempotency Rules | Read |
| Concurrency Considerations | Snapshot |
| Side Effects | None |
| Dependencies | Orders read port |
| Related Tools | ListOpenOrdersForCustomer |
| Versioning Notes | v1 |

---

## ListOpenOrdersForCustomer

| Field | Content |
|-------|---------|
| Tool Name | `ListOpenOrdersForCustomer` |
| Business Purpose | List open orders to disambiguate tracking |
| Description | Customer-scoped open orders |
| Business Intent(s) | TrackOrder, AskBalance, AskDeliveryStatus |
| Required Capabilities | `read_order_status` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | customerRef |
| Expected Inputs | customerRef; limit |
| Expected Output | orders[{ref, type, status}] |
| Possible Outcomes | List / Empty |
| Failure Scenarios | Missing customer |
| Validation Rules | Limit |
| Approval Requirements | None |
| Human Escalation Rules | None |
| Audit Events | ToolExecuted |
| Analytics Events | OrderList |
| Business Rules | Open-first |
| Security Considerations | Scope |
| Idempotency Rules | Read |
| Concurrency Considerations | — |
| Side Effects | None |
| Dependencies | Orders read |
| Related Tools | GetOrderStatus |
| Versioning Notes | v1 |

---

## CancelOrder

| Field | Content |
|-------|---------|
| Tool Name | `CancelOrder` |
| Business Purpose | Cancel a business order (high risk) |
| Description | Sensitive cancellation with financial implications |
| Business Intent(s) | CancelOrder |
| Required Capabilities | `cancel_order` |
| Required Permissions | Rarely granted |
| Allowed Modes | A (never silent) · H (Human+Approval) · F (Approval mandatory) |
| Required Context | Order; payments; policy |
| Expected Inputs | orderRef; reason; customerConfirm; approvalToken |
| Expected Output | cancelled / rejected |
| Possible Outcomes | Cancelled / Denied / PendingApproval |
| Failure Scenarios | Partial fulfillment; payments exist |
| Validation Rules | Explicit confirm; approvalToken if required |
| Approval Requirements | **Always** |
| Human Escalation Rules | **Default Yes** |
| Audit Events | Approval*, ToolExecuted |
| Analytics Events | OrderCancelAttempt |
| Business Rules | No silent refund; refund is separate forbidden-by-default tool |
| Security Considerations | Dual control |
| Idempotency Rules | Required |
| Concurrency Considerations | Status machine lock |
| Side Effects | Order cancelled; downstream inventory/delivery effects in Ops |
| Dependencies | GetOrderStatus; RequestApproval |
| Related Tools | CancelReservation (if only appointment) |
| Versioning Notes | v1 critical |

---

## TrackDelivery / GetDeliveryStatus

| Field | Content |
|-------|---------|
| Tool Name | `TrackDelivery` (alias `GetDeliveryStatus`) |
| Business Purpose | Inform delivery/pickup timing and status |
| Description | Read delivery workflow status |
| Business Intent(s) | AskDeliveryStatus |
| Required Capabilities | `read_delivery_status` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | order/delivery ref or customer |
| Expected Inputs | deliveryRef or orderRef |
| Expected Output | status, scheduled window, address hint |
| Possible Outcomes | Found / NotScheduled / NotFound |
| Failure Scenarios | Missing link |
| Validation Rules | Scope |
| Approval Requirements | None |
| Human Escalation Rules | Missed delivery disputes |
| Audit Events | ToolExecuted |
| Analytics Events | DeliveryTracked |
| Business Rules | No invented courier tracking |
| Security Considerations | Address minimization |
| Idempotency Rules | Read |
| Concurrency Considerations | — |
| Side Effects | None |
| Dependencies | Delivery read |
| Related Tools | RescheduleDelivery |
| Versioning Notes | v1 |

---

## RescheduleDelivery

| Field | Content |
|-------|---------|
| Tool Name | `RescheduleDelivery` |
| Business Purpose | Postpone or move delivery/pickup |
| Description | Bounded write on delivery schedule |
| Business Intent(s) | PostponeDelivery |
| Required Capabilities | `reschedule_delivery` |
| Required Permissions | Policy grant |
| Allowed Modes | A (Approval) · H · F (rules) |
| Required Context | Delivery; feasibility |
| Expected Inputs | deliveryRef; newWindow; customerConfirm; idempotencyKey |
| Expected Output | new schedule |
| Possible Outcomes | Rescheduled / Blocked / PendingApproval |
| Failure Scenarios | Same-day lock; inventory conflict |
| Validation Rules | Window valid; confirm |
| Approval Requirements | Often Yes |
| Human Escalation Rules | Conflicts |
| Audit Events | ToolExecuted, Approval* |
| Analytics Events | DeliveryRescheduled |
| Business Rules | Respect capacity |
| Security Considerations | Scope |
| Idempotency Rules | Required |
| Concurrency Considerations | Schedule token |
| Side Effects | Delivery updated; notify |
| Dependencies | TrackDelivery |
| Related Tools | NotifyCustomer, NotifyStaff |
| Versioning Notes | v1 |

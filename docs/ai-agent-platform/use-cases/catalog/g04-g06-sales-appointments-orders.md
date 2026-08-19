# Catalog — G04 Sales · G05 Appointments · G06 Orders

---

## UC-SALES-01 — Ask Prices

| Field | Content |
|-------|---------|
| **Goal** | Provide published pricing guidance without inventing quotes. |
| **Actors** | Customer, AI Agent, Sales (alt) |
| **Preconditions** | Price info in KB or catalog; `read_pricing` |
| **Trigger** | Ask price / cost |
| **Main Success Flow** | Clarify service (rent/tailor/sale) → retrieve published price ranges → reply + CTA |
| **Alternative Flows** | Needs custom quote → Escalate Sales; Mode=Assistant → draft for staff |
| **Failure Scenarios** | No price published → Escalate; customer demands guarantee below min → Reject+Escalate |
| **Required Context** | Service type; KB/catalog prices; Persona |
| **Required Business Tools** | `GetPublishedPricing`; optional `SearchAvailability` |
| **Required Permissions** | `read_pricing`; `reply_to_customers` |
| **Expected Output** | Price range / package info |
| **Conversation Outcome** | `LeadQualified` |
| **Audit Events** | ToolExecuted (if any), AIResponseGenerated |
| **Analytics Events** | PriceInquiry |
| **Approval?** | No for published; Yes for special quote |
| **Handover?** | Custom quote / negotiation |
| **Confidence** | High for published; else Escalate |

---

## UC-SALES-02 — Want to Rent a Dress

| Field | Content |
|-------|---------|
| **Goal** | Qualify rental need and move to availability + appointment/hold path. |
| **Actors** | Customer, AI Agent, Sales |
| **Preconditions** | Rental service offered |
| **Trigger** | Intent rent dress |
| **Main Success Flow** | Collect occasion/date/size → search availability → propose options → offer book fitting or hold per capabilities |
| **Alternative Flows** | No stock → waitlist Task; write hold allowed → `CreateRentalHold` with approval if needed |
| **Failure Scenarios** | Write denied → explain + Escalate Sales |
| **Required Context** | Date, size, prefs; CustomerRef |
| **Required Business Tools** | `SearchAvailability`; optional `CreateRentalHold`; `CreateAppointment` |
| **Required Permissions** | `read_catalog_availability`; optional `create_booking`; `create_rental_hold` |
| **Expected Output** | Options + next step |
| **Conversation Outcome** | `LeadQualified` / `Appointment` / `WaitingHuman` |
| **Audit Events** | ToolExecuted, optional ApprovalRequested |
| **Analytics Events** | RentalIntent |
| **Approval?** | Often Yes for holds/commits |
| **Handover?** | High-value / ambiguous / write denied |
| **Confidence** | Medium to proceed qualify; High before commit |

---

## UC-SALES-03 — Want Tailoring / Custom Dress

| Field | Content |
|-------|---------|
| **Goal** | Qualify tailoring request and route to fitting appointment. |
| **Actors** | Customer, AI Agent, Sales |
| **Preconditions** | Tailoring offered |
| **Trigger** | Intent tailor/custom |
| **Main Success Flow** | Ask event date, style refs, measurements need → recommend fitting → UC-APPT-01 |
| **Alternative Flows** | Timeline impossible per KB lead time → set expectation + Escalate |
| **Failure Scenarios** | Service not offered → UC-SALES-04 |
| **Required Context** | Lead-time policy; hours; Customer |
| **Required Business Tools** | `GetServiceLeadTimes`; `CreateAppointment` |
| **Required Permissions** | `read_knowledge`; `create_booking` (if auto-book) |
| **Expected Output** | Qualification + booking CTA |
| **Conversation Outcome** | `Appointment` / `LeadQualified` |
| **Audit Events** | AIResponseGenerated; ToolExecuted |
| **Analytics Events** | TailoringIntent |
| **Approval?** | If creating paid order |
| **Handover?** | Complex design consult |
| **Confidence** | Medium |

---

## UC-SALES-04 — Request Unsupported Service

| Field | Content |
|-------|---------|
| **Goal** | Decline politely; offer alternatives or human. |
| **Actors** | Customer, AI Agent |
| **Preconditions** | Service catalog known |
| **Trigger** | Ask for service not offered |
| **Main Success Flow** | Confirm unsupported from KB → apologize → list offered services → optional Escalate |
| **Alternative Flows** | Near-match service → clarify |
| **Failure Scenarios** | Agent invents service → Forbidden (invariant via KB grounding) |
| **Required Context** | Services list KB |
| **Required Business Tools** | None / KB |
| **Required Permissions** | `reply_to_customers` |
| **Expected Output** | Clear no + alternatives |
| **Conversation Outcome** | `SupportSolved` / `LeadLost` |
| **Audit Events** | AIResponseGenerated |
| **Analytics Events** | UnsupportedServiceRequested |
| **Approval?** | No |
| **Handover?** | Optional |
| **Confidence** | High if KB; else Escalate rather than guess |

---

## UC-APPT-01 — Book Fitting / Appointment

| Field | Content |
|-------|---------|
| **Goal** | Create appointment within capabilities. |
| **Actors** | Customer, AI Agent, Human Staff |
| **Preconditions** | `create_booking` or Hybrid escalate |
| **Trigger** | Book fitting / appointment |
| **Main Success Flow** | Collect date/time/branch/service → `GetAvailableSlots` → confirm slot with customer → `CreateAppointment` → confirm message |
| **Alternative Flows** | No slots → propose alternatives; Mode Assistant → draft for staff approval |
| **Failure Scenarios** | Tool deny → Escalate; double-book fail → apologize + retry |
| **Required Context** | Customer identity; service; timezone; hours |
| **Required Business Tools** | `GetAvailableSlots`; `CreateAppointment`; `LookupCustomerByContact` |
| **Required Permissions** | `create_booking`; `read_schedule` |
| **Expected Output** | Confirmation with time/place |
| **Conversation Outcome** | `Appointment` / `OrderConfirmed` (if tied) |
| **Audit Events** | ToolExecuted, optional ApprovalGranted |
| **Analytics Events** | AppointmentBooked |
| **Approval?** | Per policy / Mode |
| **Handover?** | If booking capability absent or conflict |
| **Confidence** | High before CreateAppointment |

---

## UC-APPT-02 — Confirm Booking

| Field | Content |
|-------|---------|
| **Goal** | Confirm existing appointment details. |
| **Actors** | Customer, AI Agent |
| **Preconditions** | Appointment exists or customer asserts booking |
| **Trigger** | Confirm my booking / details |
| **Main Success Flow** | Identify customer → `GetAppointment` → confirm details |
| **Alternative Flows** | Multiple appointments → list & clarify |
| **Failure Scenarios** | Not found → Clarify identifiers or Escalate |
| **Required Context** | CustomerRef; appointment pointer |
| **Required Business Tools** | `GetAppointment`; `ListAppointmentsForCustomer` |
| **Required Permissions** | `read_schedule` |
| **Expected Output** | Confirmation summary |
| **Conversation Outcome** | `SupportSolved` |
| **Audit Events** | ToolExecuted |
| **Analytics Events** | AppointmentConfirmInquiry |
| **Approval?** | No |
| **Handover?** | Identity ambiguity |
| **Confidence** | High after tool |

---

## UC-APPT-03 — Reschedule Appointment

| Field | Content |
|-------|---------|
| **Goal** | Move appointment to a new slot under policy. |
| **Actors** | Customer, AI Agent, Human Staff |
| **Preconditions** | `reschedule_booking` or approval |
| **Trigger** | Change / reschedule fitting |
| **Main Success Flow** | Find appointment → check cancel/reschedule policy → new slots → `RescheduleAppointment` → confirm |
| **Alternative Flows** | Inside lock window → Escalate; fee applies → explain policy + Approve/Escalate |
| **Failure Scenarios** | Tool fail → Escalate |
| **Required Context** | Appointment; policy; Business Hours |
| **Required Business Tools** | `GetAppointment`; `GetAvailableSlots`; `RescheduleAppointment` |
| **Required Permissions** | `reschedule_booking`; `read_knowledge` |
| **Expected Output** | New time confirmation |
| **Conversation Outcome** | `Appointment` |
| **Audit Events** | ToolExecuted, Approval* if any |
| **Analytics Events** | AppointmentRescheduled |
| **Approval?** | Often if near-term / fee |
| **Handover?** | Policy exception |
| **Confidence** | High before mutate |

---

## UC-APPT-04 — Cancel Appointment

| Field | Content |
|-------|---------|
| **Goal** | Cancel appointment per policy. |
| **Actors** | Customer, AI Agent, Human Staff |
| **Preconditions** | `cancel_booking` or Escalate |
| **Trigger** | Cancel appointment |
| **Main Success Flow** | Identify → explain policy implications → confirm intent → `CancelAppointment` → confirm |
| **Alternative Flows** | Customer hesitates → Clarify; paid/non-refundable → Escalation preferred |
| **Failure Scenarios** | Deny capability → Escalate; already started service → Escalate |
| **Required Context** | Appointment; cancellation policy |
| **Required Business Tools** | `GetAppointment`; `CancelAppointment` |
| **Required Permissions** | `cancel_booking` |
| **Expected Output** | Cancellation confirmation |
| **Conversation Outcome** | `OrderCancelled` / `SupportSolved` |
| **Audit Events** | ToolExecuted |
| **Analytics Events** | AppointmentCancelled |
| **Approval?** | Frequently Yes |
| **Handover?** | Disputes / fees |
| **Confidence** | High + explicit customer confirm |

---

## UC-ORD-01 — Track Order / Request Status

| Field | Content |
|-------|---------|
| **Goal** | Report status of rental/tailoring/sales order. |
| **Actors** | Customer, AI Agent |
| **Preconditions** | `read_order_status` |
| **Trigger** | Where is my order / status |
| **Main Success Flow** | Identify customer/order → `GetOrderStatus` → explain status + next milestone |
| **Alternative Flows** | Multiple orders → list; unknown customer → ask invoice/phone |
| **Failure Scenarios** | Not found → Clarify; tool fail → Escalate |
| **Required Context** | CustomerRef; order refs |
| **Required Business Tools** | `LookupCustomerByContact`; `ListOpenOrdersForCustomer`; `GetOrderStatus` |
| **Required Permissions** | `read_order_status` |
| **Expected Output** | Status narrative |
| **Conversation Outcome** | `SupportSolved` / `WaitingCustomer` |
| **Audit Events** | ToolExecuted |
| **Analytics Events** | OrderStatusInquiry |
| **Approval?** | No |
| **Handover?** | Angry delay complaints → UC-CMP-01 |
| **Confidence** | High after tool |

---

## UC-ORD-02 — Request Cancel Order

| Field | Content |
|-------|---------|
| **Goal** | Handle cancel-order intent safely. |
| **Actors** | Customer, AI Agent, Human Staff |
| **Preconditions** | Order exists |
| **Trigger** | Cancel order |
| **Main Success Flow** | Identify order → load policy → **do not auto-delete** → Escalate or Approval for `CancelOrder` if allowed |
| **Alternative Flows** | Soft cancel appointment only → UC-APPT-04 |
| **Failure Scenarios** | Financial impact unclear → Escalate always |
| **Required Context** | Order; payments; policy |
| **Required Business Tools** | `GetOrderStatus`; optional `CancelOrder` (sensitive) |
| **Required Permissions** | `cancel_order` (rarely granted) |
| **Expected Output** | Policy + human handling path |
| **Conversation Outcome** | `WaitingHuman` / `OrderCancelled` |
| **Audit Events** | ApprovalRequested / HumanHandoverStarted |
| **Analytics Events** | OrderCancelRequest |
| **Approval?** | Yes (default) |
| **Handover?** | Yes (default Hybrid) |
| **Confidence** | N/A — prefer human for financial cancel |

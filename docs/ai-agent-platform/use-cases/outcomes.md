# Conversation Outcomes

Official outcome vocabulary for analytics and closure. Aligns with Domain Model analytics; extends Conversation Outcome enum used in behavior specs.

| Outcome | Meaning | Typical when |
|---------|---------|--------------|
| `Sale` | Commercial sale progression attributed | Sales journey completed/paid path |
| `Appointment` | Fitting/appointment confirmed | UC-APPT-01/02 success |
| `SupportSolved` | Informational/support need met | Knowledge, status, hours |
| `ComplaintOpened` | Complaint handed to staff | UC-CMP-01 |
| `ComplaintClosed` | Staff closed complaint | After human resolution |
| `LeadQualified` | Intent + contact useful for sales | Price/rent/tailor interest |
| `LeadLost` | Abandoned or unsupported dead-end | Silence max / unsupported |
| `FollowUpRequired` | Needs later action | Proof pending, snooze |
| `WaitingCustomer` | Ball with customer | After Reply/Clarify |
| `WaitingHuman` | Ball with staff | Handover / approval |
| `OrderConfirmed` | Order/booking commit confirmed | Appointment/order confirm |
| `OrderCancelled` | Cancel completed (usually human-gated) | Cancel flows |
| `PaymentVerificationPending` | Proof received, not settled | UC-PAY-02 |
| `Blocked` | Safety stop | Abuse |
| `Abandoned` | Customer gone without resolve | Silence workflow end |

## Rules

1. A Conversation may pass through transient outcomes (`WaitingCustomer`) and end with a terminal outcome.  
2. AI must not mark `Sale` / `OrderCancelled` / `ComplaintClosed` without Tool or Human confirmation facts.  
3. Analytics Events should record outcome transitions, not only final state.

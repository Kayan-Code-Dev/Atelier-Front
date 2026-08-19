# Tool Risk Classification

| Level | Meaning | Examples |
|-------|---------|----------|
| **Low** | Read-only / no irreversible effect | GetOrderStatus, SearchKnowledge, FindAvailableSlots, GetBusinessHours, GetOutstandingBalance |
| **Medium** | Bounded writes reversible or soft-state | CreateReservation, SaveMeasurements, CreateTask, RegisterPaymentProof, CreateRentalHold, GenerateQuotation, NotifyCustomer |
| **High** | Commercial commit or case closure impact | AcceptQuotation, ResolveComplaint, SendInvoiceDocument (mis-send risk), CloseConversation with outcome |
| **Critical** | Financial settlement, identity takeover, destructive cancel, price integrity | MarkInvoicePaid, ApplyDiscount, CancelOrder, UpdateCustomerPhone |

## Policy by risk

| Risk | Approval default | Parallel | Auto-retry write | Full Auto |
|------|------------------|----------|------------------|-----------|
| Low | No | Reads OK | Yes (read) | Allowed |
| Medium | Often | No with other writes | Idempotent only | If capability |
| High | Usually Yes | No | No | Rare |
| Critical | **Always** | No | No | Approval + often Human |

## Changing risk

Raising risk class for a Tool is always allowed. Lowering Critical→lower requires Architecture ADR + security review.

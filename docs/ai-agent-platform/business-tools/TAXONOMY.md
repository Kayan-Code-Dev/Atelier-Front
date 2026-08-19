# Business Tool Taxonomy

Professional grouping of all Agent-facing Tools. Groups mirror atelier operations and Use Case groups — not database modules.

| Group | Prefix / focus | Examples |
|-------|----------------|----------|
| **Customer** | Profile & identity | `GetCustomerProfile`, `SearchCustomer`, `UpsertCustomerProfile` |
| **Lead** | Pre-customer interest (agent-side lead notes) | `CreateLeadNote`, `UpdateLeadNote` |
| **Reservation / Appointment** | Fittings & slots | `FindAvailableSlots`, `CreateReservation`, `CancelReservation` |
| **Order** | Rental / tailoring / sales orders | `GetOrderStatus`, `ListOpenOrdersForCustomer`, `CancelOrder` |
| **Invoice** | Billing documents | `GetInvoice`, `ListInvoicesForCustomer`, `SendInvoiceDocument` |
| **Payment** | Money movement proofs & links | `GetOutstandingBalance`, `RegisterPaymentProof`, `GeneratePaymentLink` |
| **Catalog** | Products / dresses / pricing publish | `SearchProducts`, `SuggestProducts`, `GetPublishedPricing` |
| **Inventory / Availability** | Stock & date availability | `SearchInventory`, `CheckItemAvailability`, `SearchAvailability` |
| **Delivery** | Pickup & delivery | `TrackDelivery`, `RescheduleDelivery` |
| **Quotation** | Formal quotes | `GenerateQuotation`, `AcceptQuotation`, `RejectQuotation` |
| **Measurements** | Size capture | `GetMeasurements`, `SaveMeasurements` |
| **Knowledge** | FAQ / policies / offers | `SearchKnowledge`, `SearchFAQ`, `SearchPolicies`, `ListPublishedOffers` |
| **Atelier Settings** | Hours, branches, methods | `GetBusinessHours`, `GetBranchLocation`, `GetAtelierSettings` |
| **Task / Follow-up** | Staff work items | `CreateTask`, `AssignTask`, `CreateFollowUp` |
| **Complaint** | Complaint cases | `CreateComplaint`, `UpdateComplaint`, `ResolveComplaint` |
| **Conversation Ops** | Memory, summary, close, transfer | `StoreConversationMemory`, `GenerateConversationSummary`, `CloseConversation`, `TransferConversation` |
| **Collaboration / Approval** | Human gates | `RequestApproval`, `SubmitApprovalDecision`, `NotifyStaff` |
| **Notification** | Outbound notices (staff/customer via allowed paths) | `NotifyCustomer`, `NotifyStaff` |
| **Automation Support** | Used by workflows | Reminder reads, follow-up creates |
| **Analytics Support** | Explicit metric hooks (rare; usually auto) | Outcome markers if needed |

## Side-effect classes (cross-cutting)

| Class | Meaning | Typical groups |
|-------|---------|----------------|
| **Read** | No mutation | Catalog, Invoice read, Order status, Knowledge |
| **WriteBounded** | Creates/updates within policy | Reservation create, SaveMeasurements |
| **SensitiveWrite** | Financial / identity / cancel | CancelOrder, ApplyDiscount, UpdateCustomerPhone, AcceptQuotation |
| **Collaboration** | Staff/queue effects | CreateTask, TransferConversation, RequestApproval |
| **KnowledgeRead** | Published content only | SearchFAQ, SearchPolicies |

## Catalog file map

| File | Groups |
|------|--------|
| [contracts/customer-lead.md](./contracts/customer-lead.md) | Customer, Lead |
| [contracts/reservation-order-delivery.md](./contracts/reservation-order-delivery.md) | Reservation, Order, Delivery |
| [contracts/invoice-payment-quotation.md](./contracts/invoice-payment-quotation.md) | Invoice, Payment, Quotation |
| [contracts/catalog-inventory-measurements.md](./contracts/catalog-inventory-measurements.md) | Catalog, Inventory, Measurements |
| [contracts/knowledge-settings.md](./contracts/knowledge-settings.md) | Knowledge, Settings |
| [contracts/tasks-complaints-collaboration.md](./contracts/tasks-complaints-collaboration.md) | Tasks, Complaints, Approval, Notify, Transfer |
| [contracts/conversation-ops.md](./contracts/conversation-ops.md) | Memory, Summary, Close |

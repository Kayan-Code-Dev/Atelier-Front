# Intent Mapping

Default mode assumed: **Hybrid**. Confidence bands: High ≥ 0.85 · Medium ≥ 0.65 · Low < 0.65.

| Intent | Business Tools | Permissions (capabilities) | Min Confidence to act | Approval? | Human? |
|--------|----------------|----------------------------|------------------------|-----------|--------|
| `Greeting` | optional LookupCustomer | `reply_to_customers` | Medium | No | No |
| `Goodbye` | — | `reply_to_customers` | Medium | No | If open sensitive task |
| `SmallTalk` | — | `reply_to_customers` | Medium | No | Safety only |
| `AskWorkingHours` | GetAtelierSettings / KB | `read_atelier_settings` | High | No | If missing data |
| `AskLocation` | GetAtelierSettings / KB | `read_atelier_settings` | High | No | If missing |
| `AskCancellationPolicy` | KB | `read_knowledge` | High | No | Dispute → Yes |
| `AskSeasonalOffers` | ListPublishedOffers / KB | `read_offers` | High | No | Custom discount → Yes |
| `AskPaymentMethods` | Settings / KB | `read_atelier_settings` | High | No | If missing |
| `AskAvailability` | SearchAvailability | `read_catalog_availability` | Medium to clarify; High after facts | No | VIP policy |
| `CheckItemAvailability` | ResolveProduct, CheckItemAvailability | `read_catalog_availability` | High after tool | No | Hold without cap → Yes |
| `AskSizeGuide` | KB | `read_knowledge` | High | No | Complex bridal → optional |
| `AskPrice` | GetPublishedPricing | `read_pricing` | High for published | Special quote Yes | Negotiation Yes |
| `RentDress` | SearchAvailability, CreateAppointment/Hold | read + optional create_* | Medium qualify; High commit | Hold/commit often Yes | Write denied Yes |
| `RequestTailoring` | GetServiceLeadTimes, CreateAppointment | read + optional create_booking | Medium | Order create Yes | Design consult Yes |
| `UnsupportedService` | KB | `reply_to_customers` | High | No | Optional |
| `BookAppointment` | GetAvailableSlots, CreateAppointment | `read_schedule`, `create_booking` | **High** | Per policy | If no capability |
| `ConfirmAppointment` | GetAppointment | `read_schedule` | High | No | Ambiguous id |
| `RescheduleAppointment` | GetAppointment, GetAvailableSlots, RescheduleAppointment | `reschedule_booking` | **High** | Near-term/fee Yes | Exceptions Yes |
| `CancelAppointment` | GetAppointment, CancelAppointment | `cancel_booking` | **High** + confirm | Often Yes | Fees/disputes Yes |
| `TrackOrder` | LookupCustomer, ListOpenOrders, GetOrderStatus | `read_order_status` | High after tool | No | Angry delay → complaint |
| `CancelOrder` | GetOrderStatus, CancelOrder | `cancel_order` (rare) | N/A prefer human | **Yes** | **Yes default** |
| `AskInvoice` | ListInvoices, GetInvoice, optional SendInvoiceDocument | `read_invoice` | High | Doc send maybe | Identity risk |
| `AskBalance` | GetCustomerBalance, GetInvoice | `read_balance` | High | No | Dispute Yes |
| `SubmitPaymentProof` | RegisterPaymentProof, CreateStaffTask | `register_payment_proof` (not mark_paid) | Medium ack | **Yes** before paid | **Yes typical** |
| `AskDeliveryStatus` | GetDeliveryStatus | `read_delivery_status` | High | No | Disputes Yes |
| `PostponeDelivery` | GetDeliveryStatus, RescheduleDelivery | `reschedule_delivery` | **High** | Often Yes | Common Hybrid |
| `Complaint` | CreateStaffTask, lookups | reply + task | Low/Med → escalate | Remedies Yes | **Yes default** |
| `RequestDiscount` | ListPublishedOffers, ApplyDiscount rare | `read_offers` / `apply_discount` | High only published | **Yes** non-published | **Yes typical** |
| `RequestException` / `RequestManager` | CreateStaffTask | escalate | N/A | Human | **Yes** |
| `EscalateHuman` | CreateStaffTask | escalate | Explicit | No | **Yes** |
| `ProvideCustomerData` | UpsertCustomerProfile / SaveCustomerNote | write_customer_* | Medium | No basic | KYC Yes |
| `ChangePhoneNumber` | UpdateCustomerPhone | `update_customer_phone` | **High**+verify | **Yes** | **Yes default** |
| `SendImage` | optional MatchCatalogByImage | reply | Low without tool | No ack | Styling optional |
| `SendVoiceNote` | TranscribeAudio optional | reply | = transcript | No | If no STT |
| `SendDocument` | RegisterPaymentProof / Task | proof/task | Low auto-class | Before finance | Typical |
| `UnknownIntent` | — | reply | — | No | If repeated Low |
| `ConflictingIntents` | — | reply | — | No | If unresolved |
| `SafetyViolation` | — | safety | — | No | **Yes** + Block |
| `AppointmentReminder` | GetAppointment | outbound reply | N/A | No | No |
| `FeedbackRequest` | optional Task | outbound reply | N/A | No | Negative → Yes |

## Mapping rules

1. Below threshold → **Clarify**, never mutate.  
2. Permission Deny → explain limits + **Escalate** (do not loop).  
3. `RequireHumanApproval` → ApprovalRequest; Mode Assistant may stop at draft.  
4. Full Auto still obeys this table’s Approval/Human columns for sensitive intents.

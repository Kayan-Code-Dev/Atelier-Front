# 04 — Business Tool Catalog (DressnMore)

**Source of truth for contracts:** `docs/ai-agent-platform/business-tools/`  
This catalog organizes required tools by operational domain for AOS ↔ DressnMore binding.

Risk: **L** Low · **M** Medium · **H** High · **C** Critical  
Approval: **No** · **Often** · **Always**

---

## Customers

| Tool | Purpose | Inputs | Outputs | Risk | Approval | Capabilities |
|------|---------|--------|---------|------|----------|--------------|
| GetCustomerProfile | Read customer twin | customerRef / channel id | profile | L | No | read_customer_profile |
| SearchCustomer | Find customers | query, phone, name | candidates[] | L | No | search_customers |
| UpsertCustomerProfile | Create/update profile | profile fields | customerRef | M | Often | write_customer_profile |
| SaveCustomerNote | Store note | customerRef, note | noteId | L | No | write_customer_notes |
| UpdateCustomerPhone | Change phone | customerRef, phone | updated | C | Always | update_customer_phone |

## Leads / Marketing intake

| Tool | Purpose | Inputs | Outputs | Risk | Approval | Capabilities |
|------|---------|--------|---------|------|----------|--------------|
| CreateLeadNote | Capture interest | contact, interest | leadRef | L | No | write_lead_notes |
| UpdateLeadNote | Update lead | leadRef, fields | lead | L | No | write_lead_notes |

## Reservations

| Tool | Purpose | Inputs | Outputs | Risk | Approval | Capabilities |
|------|---------|--------|---------|------|----------|--------------|
| FindAvailableSlots | Slot search | service, date range | slots[] | L | No | read_schedule |
| GetReservation | Read booking | reservationRef | reservation | L | No | read_schedule |
| ListReservationsForCustomer | List bookings | customerRef | list | L | No | read_schedule |
| CreateReservation | Book | customer, slot, service | reservationRef | M | Often | create_booking |
| RescheduleReservation | Move booking | reservationRef, new slot | updated | M | Often | reschedule_booking |
| CancelReservation | Cancel booking | reservationRef, reason | cancelled | M | Often | cancel_booking |

## Orders (Sales / Tailoring / Rental)

| Tool | Purpose | Inputs | Outputs | Risk | Approval | Capabilities |
|------|---------|--------|---------|------|----------|--------------|
| GetOrderStatus | Status | orderRef | status | L | No | read_order_status |
| ListOpenOrdersForCustomer | Open orders | customerRef | orders[] | L | No | read_order_status |
| CancelOrder | Cancel order | orderRef | cancelled | C | Always | cancel_order |
| CreateRentalHold | Hold item | productRef, dates | holdRef | M | Often | create_rental_hold |

## Tailoring

| Tool | Purpose | Inputs | Outputs | Risk | Approval | Capabilities |
|------|---------|--------|---------|------|----------|--------------|
| GetMeasurements | Read sizes | customerRef | measurements | L | No | read_measurements |
| SaveMeasurements | Save sizes | customerRef, measures | ok | M | Optional | write_measurements |
| GetServiceLeadTimes | Lead times | serviceType | ETA | L | No | read_knowledge / settings |

## Rental / Catalog / Inventory

| Tool | Purpose | Inputs | Outputs | Risk | Approval | Capabilities |
|------|---------|--------|---------|------|----------|--------------|
| SearchProducts | Catalog search | attrs/query | products[] | L | No | read_catalog_availability |
| SuggestProducts | Ranked suggestions | prefs | products[] | L | No | read_catalog_availability |
| ResolveProduct | Map wording→ref | utterance | productRef/candidates | L | No | read_catalog_availability |
| MatchCatalogByImage | Visual match | mediaRef | candidates | M | No | match_catalog_image |
| SearchInventory | Stock search | filters | items[] | L | No | read_catalog_availability |
| CheckItemAvailability | Date availability | productRef, date | yes/no | L | No | read_catalog_availability |
| SearchAvailability | Broader availability | constraints | options[] | L | No | read_catalog_availability |
| GetPublishedPricing | Published prices | productRef | price | L | No | read_pricing |
| ListPublishedOffers | Offers | scope | offers[] | L | No | read_offers |

## Invoices / Quotations

| Tool | Purpose | Inputs | Outputs | Risk | Approval | Capabilities |
|------|---------|--------|---------|------|----------|--------------|
| GetInvoice | Invoice read | invoiceRef | invoice | L | No | read_invoice |
| ListInvoicesForCustomer | List invoices | customerRef | list | L | No | read_invoice |
| SendInvoiceDocument | Send PDF/link | invoiceRef, channel | sent | M | Often | send_invoice_document |
| GenerateQuotation | Create quote | items, customer | quotationRef | M | Custom pricing | create_quotation |
| AcceptQuotation | Accept quote | quotationRef | accepted | H | Usually | accept_quotation |
| RejectQuotation | Reject quote | quotationRef | rejected | L | No | reject_quotation |
| ApplyDiscount | Discount | docRef, %/amount | applied | C | Always | apply_discount |

## Payments / Accounting / Cashbox (agent-facing subset)

| Tool | Purpose | Inputs | Outputs | Risk | Approval | Capabilities |
|------|---------|--------|---------|------|----------|--------------|
| GetOutstandingBalance | Balance due | customer/invoice | amount | L | No | read_balance |
| RegisterPaymentProof | Upload proof | invoiceRef, proof | proofId | M | Settle separate | register_payment_proof |
| GeneratePaymentLink | Pay link | invoiceRef | url | M | Per policy | generate_payment_link |
| MarkInvoicePaid | Mark paid | invoiceRef | paid | C | Always | mark_paid |

> Full ledger/cashbox postings remain **DressnMore accounting internals**; Agent uses payment/invoice tools only.

## Delivery

| Tool | Purpose | Inputs | Outputs | Risk | Approval | Capabilities |
|------|---------|--------|---------|------|----------|--------------|
| TrackDelivery | Track | deliveryRef | status | L | No | read_delivery_status |
| RescheduleDelivery | Reschedule | deliveryRef, slot | updated | M | Often | reschedule_delivery |

## HR

| Tool | Purpose | Inputs | Outputs | Risk | Approval | Capabilities |
|------|---------|--------|---------|------|----------|--------------|
| — | **Out of Agent default scope** | — | — | — | — | — |

> HR is Tenant Ops; not exposed as default Digital Employee tools unless a future policy pack explicitly enables staff-assist tools.

## Reports / Analytics

| Tool | Purpose | Inputs | Outputs | Risk | Approval | Capabilities |
|------|---------|--------|---------|------|----------|--------------|
| (implicit) | Outcome markers via Gateway analytics events | tool results | metrics | L | No | analytics hooks |

> Prefer automatic analytics events over agent-callable report tools.

## Settings / Knowledge

| Tool | Purpose | Inputs | Outputs | Risk | Approval | Capabilities |
|------|---------|--------|---------|------|----------|--------------|
| SearchKnowledge | KB search | query | hits | L | No | read_knowledge |
| SearchFAQ | FAQ | query | answers | L | No | read_knowledge |
| SearchPolicies | Policies | query | policies | L | No | read_knowledge |
| GetAtelierSettings | Settings | keys | values | L | No | read_atelier_settings |
| GetBusinessHours | Hours | branch? | hours | L | No | read_atelier_settings |
| GetBranchLocation | Location | branch | address | L | No | read_atelier_settings |

## Notifications / Collaboration / Conversation Ops

| Tool | Purpose | Inputs | Outputs | Risk | Approval | Capabilities |
|------|---------|--------|---------|------|----------|--------------|
| NotifyStaff | Staff alert | message, audience | sent | L | No | notify_staff |
| NotifyCustomer | Customer notice | message, channel | sent | M | Opt-in rules | notify_customer |
| CreateTask | Staff task | title, due | taskId | L | No | create_internal_task |
| AssignTask | Assign | taskId, user | ok | L | Optional | assign_internal_task |
| CreateFollowUp | Follow-up | when, topic | followUpId | L | No | create_follow_up |
| CreateComplaint | Open complaint | details | complaintId | M | No | write_complaint |
| UpdateComplaint | Update | complaintId, fields | ok | L | No | write_complaint |
| ResolveComplaint | Resolve | complaintId | resolved | H | Yes | resolve_complaint |
| TransferConversation | Handover | conversationId, queue | ownership | M | No | escalate_to_human |
| RequestApproval | Gate | action, reason | approvalId | L | Meta | request_approval |
| SubmitApprovalDecision | Decide | approvalId, decision | done | M | Staff | decide_approval |
| StoreConversationMemory | Persist fact | fact payload | memoryId | L | No | write_conversation_memory |
| GenerateConversationSummary | Summary | conversationId | summary | L | No | summarize_conversation |
| CloseConversation | Close | conversationId | closed | M | Optional | close_conversation |
| TranscribeAudio | STT | mediaRef | text | L | No | transcribe_audio |

## AI / Utilities

| Tool | Purpose | Notes |
|------|---------|-------|
| Provider completion | Via `aos.ai` — **not** a Business Tool | Keep LLM calls out of Tool Gateway |
| Workflow tasks | Via `aos.workflow` task ports | May call Tools indirectly through adapters |

## Binding rule

DressnMore integration implements **adapters for these contracts** only. Adding a tool requires Taxonomy + Contract + Capability Matrix + Permission key — not a direct Domain service call from Agent Core.

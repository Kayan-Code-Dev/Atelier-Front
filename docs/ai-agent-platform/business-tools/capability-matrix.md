# Tool Capability Matrix

| Tool | Capabilities | Modes | Approval | Human escalate | Risk |
|------|--------------|-------|----------|----------------|------|
| GetCustomerProfile | read_customer_profile | A H F | No | Ambiguous id | Low |
| SearchCustomer | search_customers | A H F | No | TooMany | Low |
| UpsertCustomerProfile | write_customer_profile | A* H F | Often H | Merge/KYC | Medium |
| SaveCustomerNote | write_customer_notes | A H F | No | — | Low |
| UpdateCustomerPhone | update_customer_phone | A* H F* | **Always** | **Default** | Critical |
| CreateLeadNote / UpdateLeadNote | write_lead_notes | A H F | No | VIP optional | Low |
| FindAvailableSlots | read_schedule | A H F | No | — | Low |
| GetReservation / ListReservations | read_schedule | A H F | No | Ambiguous | Low |
| CreateReservation | create_booking | A* H F* | Often | Conflicts | Medium |
| RescheduleReservation | reschedule_booking | A* H F* | Often near-term | Exceptions | Medium |
| CancelReservation | cancel_booking | A* H F* | Often | Fee disputes | Medium |
| GetOrderStatus / ListOpenOrders | read_order_status | A H F | No | — | Low |
| CancelOrder | cancel_order | A* H F* | **Always** | **Default** | Critical |
| TrackDelivery | read_delivery_status | A H F | No | Disputes | Low |
| RescheduleDelivery | reschedule_delivery | A* H F* | Often | Conflicts | Medium |
| GetInvoice / ListInvoices | read_invoice | A H F | No | Identity | Low |
| SendInvoiceDocument | send_invoice_document | A* H F* | Often | Wrong recipient risk | Medium |
| GetOutstandingBalance | read_balance | A H F | No | Dispute | Low |
| RegisterPaymentProof | register_payment_proof | A H F | No (settle separate) | Typical staff | Medium |
| GeneratePaymentLink | generate_payment_link | A* H F* | Per policy | Unsupported | Medium |
| MarkInvoicePaid | mark_paid | H* F* | **Always** | **Default** | Critical |
| GenerateQuotation | create_quotation | A* H F* | Custom pricing | Negotiation | Medium |
| AcceptQuotation | accept_quotation | A* H F* | Usually | High value | High |
| RejectQuotation | reject_quotation | A H F | No | — | Low |
| ApplyDiscount | apply_discount | H* F* | **Always** | **Default** | Critical |
| SearchProducts / SuggestProducts / ResolveProduct | read_catalog_availability | A H F | No | — | Low |
| SearchAvailability / CheckItemAvailability | read_catalog_availability | A H F | No | Hold demand | Low |
| CreateRentalHold | create_rental_hold | A* H F* | Often | VIP items | Medium |
| GetPublishedPricing / ListPublishedOffers | read_pricing / read_offers | A H F | No | Not published | Low |
| GetServiceLeadTimes | read_knowledge / settings | A H F | No | Rush | Low |
| GetMeasurements | read_measurements | A H F | No | — | Low |
| SaveMeasurements | write_measurements | A H F | Optional | — | Medium |
| SearchKnowledge / FAQ / Policies | read_knowledge | A H F | No | Empty critical | Low |
| GetAtelierSettings / Hours / Location | read_atelier_settings | A H F | No | Incomplete | Low |
| CreateTask / CreateFollowUp | create_internal_task / create_follow_up | A H F | No | — | Low |
| AssignTask | assign_internal_task | A* H F | Optional | — | Low |
| CreateComplaint | write_complaint | A H F | No | Pair handover | Medium |
| UpdateComplaint | write_complaint | A H F | No | — | Low |
| ResolveComplaint | resolve_complaint | H* F* | Yes | **Default** | High |
| TransferConversation | escalate_to_human | A H F | No | Self | Medium |
| RequestApproval | request_approval | A H F | Meta | Timeout | Low |
| SubmitApprovalDecision | decide_approval (staff) | Staff | Meta | — | Medium |
| NotifyStaff | notify_staff | A H F | No | — | Low |
| NotifyCustomer | notify_customer | A* H F | Opt-in rules | Fail | Medium |
| StoreConversationMemory | write_conversation_memory | A H F | No | — | Low |
| GenerateConversationSummary | summarize_conversation | A H F | No | — | Low |
| CloseConversation | close_conversation | A* H F | Optional | Pending work | Medium |
| MatchCatalogByImage | match_catalog_image | A H F | No | Low conf | Medium |
| TranscribeAudio | transcribe_audio | A H F | No | No STT | Low |

\* = restricted / approval-gated in that mode per contract.

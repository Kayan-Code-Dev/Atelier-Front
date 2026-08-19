# Catalog — G07 Invoices · G08 Payments · G09 Delivery

---

## UC-INV-01 — Request Invoice Copy

| Field | Content |
|-------|---------|
| **Goal** | Provide invoice summary or send copy via allowed channel means. |
| **Actors** | Customer, AI Agent |
| **Preconditions** | `read_invoice` |
| **Trigger** | Need invoice / copy |
| **Main Success Flow** | Identify customer → `GetInvoice` → share allowed summary; if PDF send supported & permitted → send; else summarize + offer staff email |
| **Alternative Flows** | Multiple invoices → list; identity weak → Clarify |
| **Failure Scenarios** | PII policy blocks full send → Escalate |
| **Required Context** | CustomerRef; invoice id |
| **Required Business Tools** | `ListInvoicesForCustomer`; `GetInvoice`; optional `SendInvoiceDocument` |
| **Required Permissions** | `read_invoice`; optional `send_invoice_document` |
| **Expected Output** | Invoice facts / document |
| **Conversation Outcome** | `SupportSolved` |
| **Audit Events** | ToolExecuted |
| **Analytics Events** | InvoiceCopyRequest |
| **Approval?** | Maybe for document send |
| **Handover?** | Identity risk |
| **Confidence** | High after identity+tool |

---

## UC-INV-02 — Ask Outstanding Balance / Deposit

| Field | Content |
|-------|---------|
| **Goal** | State remaining balance/deposit factually. |
| **Actors** | Customer, AI Agent |
| **Preconditions** | `read_balance` |
| **Trigger** | How much do I owe / deposit left |
| **Main Success Flow** | Identify → `GetCustomerBalance` / invoice balance → reply amount + payment methods CTA |
| **Alternative Flows** | Dispute amount → Escalate (no AI adjust) |
| **Failure Scenarios** | Tool deny → Escalate |
| **Required Context** | Customer; open invoices |
| **Required Business Tools** | `GetCustomerBalance`; `GetInvoice` |
| **Required Permissions** | `read_balance` |
| **Expected Output** | Amount due |
| **Conversation Outcome** | `WaitingCustomer` / `SupportSolved` |
| **Audit Events** | ToolExecuted |
| **Analytics Events** | BalanceInquiry |
| **Approval?** | No for read |
| **Handover?** | On dispute |
| **Confidence** | High after tool |

---

## UC-PAY-01 — Ask Payment Methods

| Field | Content |
|-------|---------|
| **Goal** | Explain accepted payment methods from Settings/KB. |
| **Actors** | Customer, AI Agent |
| **Preconditions** | Methods published |
| **Trigger** | How can I pay |
| **Main Success Flow** | Reply methods + any transfer instructions from KB (no inventing accounts) |
| **Alternative Flows** | Missing config → Escalate |
| **Failure Scenarios** | Customer asks unofficial method → Reject |
| **Required Context** | Settings/KB payments |
| **Required Business Tools** | `GetAtelierSettings` / KB |
| **Required Permissions** | `read_atelier_settings` |
| **Expected Output** | Methods list |
| **Conversation Outcome** | `SupportSolved` |
| **Audit Events** | AIResponseGenerated |
| **Analytics Events** | PaymentMethodsInquiry |
| **Approval?** | No |
| **Handover?** | If missing |
| **Confidence** | High |

---

## UC-PAY-02 — Customer Sends Transfer Receipt

| Field | Content |
|-------|---------|
| **Goal** | Accept proof, acknowledge, route for verification — AI does not mark paid without capability+approval. |
| **Actors** | Customer, AI Agent, Human Staff / Finance |
| **Preconditions** | Media or text receipt inbound |
| **Trigger** | Image/PDF/text claiming payment |
| **Main Success Flow** | Acknowledge receipt → attach to Conversation → create Task/`RegisterPaymentProof` if allowed → inform verification pending → Notify staff |
| **Alternative Flows** | Clear invoice reference → link Business Object Ref |
| **Failure Scenarios** | Unreadable media → Clarify ask resend; AI auto-mark paid Forbidden without grant |
| **Required Context** | Customer; open invoices; media metadata |
| **Required Business Tools** | `RegisterPaymentProof`; `CreateStaffTask`; optional `GetInvoice` |
| **Required Permissions** | `register_payment_proof`; **not** `mark_paid` by default |
| **Expected Output** | Ack + verification pending |
| **Conversation Outcome** | `WaitingHuman` / `FollowUpRequired` |
| **Audit Events** | MessageReceived, TaskCreated, NotificationEmitted |
| **Analytics Events** | PaymentProofReceived |
| **Approval?** | Yes before marking paid |
| **Handover?** | Yes typical |
| **Confidence** | Medium for ack; High human for settle |

---

## UC-DEL-01 — Ask Delivery / Pickup Timing

| Field | Content |
|-------|---------|
| **Goal** | Inform delivery/pickup schedule for an order. |
| **Actors** | Customer, AI Agent |
| **Preconditions** | `read_delivery_status` |
| **Trigger** | When will it arrive / pickup time |
| **Main Success Flow** | Identify order → `GetDeliveryStatus` → reply schedule + instructions |
| **Alternative Flows** | Not scheduled yet → say pending + offer human |
| **Failure Scenarios** | Tool fail → Escalate |
| **Required Context** | Order/delivery refs |
| **Required Business Tools** | `GetDeliveryStatus`; `GetOrderStatus` |
| **Required Permissions** | `read_delivery_status` |
| **Expected Output** | Timing info |
| **Conversation Outcome** | `SupportSolved` |
| **Audit Events** | ToolExecuted |
| **Analytics Events** | DeliveryInquiry |
| **Approval?** | No |
| **Handover?** | Missed delivery disputes |
| **Confidence** | High after tool |

---

## UC-DEL-02 — Postpone Delivery / Pickup

| Field | Content |
|-------|---------|
| **Goal** | Reschedule delivery/pickup under policy. |
| **Actors** | Customer, AI Agent, Human Staff |
| **Preconditions** | `reschedule_delivery` or Escalate |
| **Trigger** | Delay pickup/delivery |
| **Main Success Flow** | Identify → check feasibility → propose slots → `RescheduleDelivery` or Escalate |
| **Alternative Flows** | Same-day lock → Escalate |
| **Failure Scenarios** | Inventory conflict → explain + human |
| **Required Context** | Delivery; availability; policy |
| **Required Business Tools** | `GetDeliveryStatus`; `RescheduleDelivery` |
| **Required Permissions** | `reschedule_delivery` |
| **Expected Output** | New schedule or handover |
| **Conversation Outcome** | `FollowUpRequired` / `WaitingHuman` |
| **Audit Events** | ToolExecuted / HumanHandoverStarted |
| **Analytics Events** | DeliveryPostponeRequest |
| **Approval?** | Often Yes |
| **Handover?** | Common in Hybrid |
| **Confidence** | High before mutate |

# Contracts — Invoice, Payment, Quotation

---

## GetInvoice

| Field | Content |
|-------|---------|
| Tool Name | `GetInvoice` |
| Business Purpose | Retrieve invoice facts for customer inquiry |
| Description | Permission-filtered invoice view |
| Business Intent(s) | AskInvoice, AskBalance |
| Required Capabilities | `read_invoice` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | customer scope |
| Expected Inputs | invoiceRef or (customerRef + hint) |
| Expected Output | totals, status, line summary, due date |
| Possible Outcomes | Found / NotFound / Ambiguous |
| Failure Scenarios | Scope fail |
| Validation Rules | Tenant + customer ownership |
| Approval Requirements | None for read |
| Human Escalation Rules | Identity weak |
| Audit Events | ToolExecuted |
| Analytics Events | InvoiceRead |
| Business Rules | No hidden internal cost fields |
| Security Considerations | PII/financial minimization |
| Idempotency Rules | Read |
| Concurrency Considerations | Snapshot |
| Side Effects | None |
| Dependencies | Invoice read port |
| Related Tools | ListInvoicesForCustomer, SendInvoiceDocument |
| Versioning Notes | v1 |

---

## ListInvoicesForCustomer

| Field | Content |
|-------|---------|
| Tool Name | `ListInvoicesForCustomer` |
| Business Purpose | List invoices for disambiguation |
| Description | Customer invoice list |
| Business Intent(s) | AskInvoice, AskBalance |
| Required Capabilities | `read_invoice` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | customerRef |
| Expected Inputs | customerRef; statusFilter?; limit |
| Expected Output | invoices[] |
| Possible Outcomes | List / Empty |
| Failure Scenarios | Missing customer |
| Validation Rules | Limit |
| Approval Requirements | None |
| Human Escalation Rules | None |
| Audit Events | ToolExecuted |
| Analytics Events | InvoiceList |
| Business Rules | Recent-first |
| Security Considerations | Scope |
| Idempotency Rules | Read |
| Concurrency Considerations | — |
| Side Effects | None |
| Dependencies | Invoice read |
| Related Tools | GetInvoice |
| Versioning Notes | v1 |

---

## SendInvoiceDocument

| Field | Content |
|-------|---------|
| Tool Name | `SendInvoiceDocument` |
| Business Purpose | Deliver invoice document to customer via allowed channel path |
| Description | Triggers document send; not a raw file dump into prompt |
| Business Intent(s) | AskInvoice |
| Required Capabilities | `send_invoice_document` |
| Required Permissions | Policy grant |
| Allowed Modes | A (Approval) · H (maybe Approval) · F (if granted) |
| Required Context | invoiceRef; customer channel address |
| Expected Inputs | invoiceRef; destinationHint? |
| Expected Output | sendAccepted flag |
| Possible Outcomes | SentAccepted / Denied / Failed |
| Failure Scenarios | Channel cannot carry docs; policy block |
| Validation Rules | Invoice belongs to customer; destination matches Contact |
| Approval Requirements | Often Yes |
| Human Escalation Rules | Identity risk |
| Audit Events | ToolExecuted |
| Analytics Events | InvoiceDocumentSent |
| Business Rules | Prefer summary via GetInvoice if send denied |
| Security Considerations | Prevent sending to wrong Contact |
| Idempotency Rules | Recommended |
| Concurrency Considerations | — |
| Side Effects | Outbound document via notification/channel send path |
| Dependencies | GetInvoice; NotifyCustomer path |
| Related Tools | GetInvoice |
| Versioning Notes | v1 |

---

## GetOutstandingBalance

| Field | Content |
|-------|---------|
| Tool Name | `GetOutstandingBalance` |
| Business Purpose | Tell customer what remains due / deposit state |
| Description | Aggregated balance view |
| Business Intent(s) | AskBalance |
| Required Capabilities | `read_balance` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | customerRef |
| Expected Inputs | customerRef; optional invoiceRef |
| Expected Output | amountDue; currency; depositHeld?; asOf |
| Possible Outcomes | Balance / Zero / NotFound |
| Failure Scenarios | Accounting unavailable |
| Validation Rules | Scope |
| Approval Requirements | None |
| Human Escalation Rules | Dispute amounts |
| Audit Events | ToolExecuted |
| Analytics Events | BalanceRead |
| Business Rules | AI cannot adjust balances |
| Security Considerations | No ledger dump |
| Idempotency Rules | Read |
| Concurrency Considerations | Snapshot |
| Side Effects | None |
| Dependencies | Balance read port |
| Related Tools | GetInvoice, RegisterPaymentProof |
| Versioning Notes | v1 |

---

## RegisterPaymentProof

| Field | Content |
|-------|---------|
| Tool Name | `RegisterPaymentProof` |
| Business Purpose | Record that customer submitted payment evidence |
| Description | Stores proof reference; does **not** mark invoice paid |
| Business Intent(s) | SubmitPaymentProof |
| Required Capabilities | `register_payment_proof` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F (ack path) |
| Required Context | Conversation media refs; customer; optional invoice |
| Expected Inputs | customerRef; mediaRef; invoiceRef?; amountClaimed?; idempotencyKey |
| Expected Output | proofId; verificationStatus=Pending |
| Possible Outcomes | Registered / Denied / Duplicate |
| Failure Scenarios | Missing media; invalid invoice link |
| Validation Rules | Media required; amount optional positive |
| Approval Requirements | Not for register; **Yes** before any MarkPaid |
| Human Escalation Rules | Typical NotifyStaff + Task |
| Audit Events | ToolExecuted |
| Analytics Events | PaymentProofRegistered |
| Business Rules | Never auto MarkPaid |
| Security Considerations | Malware scanning conceptual; no trust claim amount |
| Idempotency Rules | Required |
| Concurrency Considerations | Dedupe media hash |
| Side Effects | Proof record; staff notification recommended |
| Dependencies | Media message refs |
| Related Tools | CreateTask, NotifyStaff, GetOutstandingBalance |
| Versioning Notes | v1 |

---

## GeneratePaymentLink

| Field | Content |
|-------|---------|
| Tool Name | `GeneratePaymentLink` |
| Business Purpose | Provide a payment link when atelier supports it |
| Description | Creates/returns payment URL for an invoice/amount |
| Business Intent(s) | AskBalance, AskPaymentMethods |
| Required Capabilities | `generate_payment_link` |
| Required Permissions | Policy grant |
| Allowed Modes | A (Approval) · H · F (if granted) |
| Required Context | invoice/order; customer |
| Expected Inputs | invoiceRef or amount+customerRef; idempotencyKey |
| Expected Output | paymentUrl; expiry |
| Possible Outcomes | Created / Unsupported / Denied |
| Failure Scenarios | Gateway not configured |
| Validation Rules | Amount>0; beneficiary is atelier |
| Approval Requirements | Per policy |
| Human Escalation Rules | Unsupported → human instructions |
| Audit Events | ToolExecuted |
| Analytics Events | PaymentLinkGenerated |
| Business Rules | Link must match invoice tenant |
| Security Considerations | No open amount abuse without invoice |
| Idempotency Rules | Required |
| Concurrency Considerations | — |
| Side Effects | Payment session created in Ops/payments |
| Dependencies | Payments port |
| Related Tools | GetOutstandingBalance |
| Versioning Notes | v1 |

---

## MarkInvoicePaid *(Critical — deny by default)*

| Field | Content |
|-------|---------|
| Tool Name | `MarkInvoicePaid` |
| Business Purpose | Settle invoice as paid after verification |
| Description | Critical financial write — normally staff-only |
| Business Intent(s) | SubmitPaymentProof (after human) |
| Required Capabilities | `mark_paid` |
| Required Permissions | Deny-by-default |
| Allowed Modes | A no · H Approval+Human · F Approval mandatory rare |
| Required Context | proofId; invoiceRef; approvalToken |
| Expected Inputs | invoiceRef; proofId; amount; approvalToken |
| Expected Output | paid status |
| Possible Outcomes | Paid / Denied / PendingApproval |
| Failure Scenarios | Amount mismatch |
| Validation Rules | approvalToken; proof must exist |
| Approval Requirements | **Always** |
| Human Escalation Rules | Default |
| Audit Events | Approval*, ToolExecuted |
| Analytics Events | InvoiceMarkedPaid |
| Business Rules | Dual control |
| Security Considerations | Critical |
| Idempotency Rules | Required |
| Concurrency Considerations | Financial lock |
| Side Effects | Payment posted |
| Dependencies | RegisterPaymentProof, RequestApproval |
| Related Tools | GetOutstandingBalance |
| Versioning Notes | v1 critical |

---

## GenerateQuotation

| Field | Content |
|-------|---------|
| Tool Name | `GenerateQuotation` |
| Business Purpose | Create formal quotation for customer |
| Description | Builds quotation from selected items/services within pricing rules |
| Business Intent(s) | AskPrice, RentDress, RequestTailoring |
| Required Capabilities | `create_quotation` |
| Required Permissions | Policy grant |
| Allowed Modes | A Approval · H often Approval · F if allowed |
| Required Context | customer; items; published pricing |
| Expected Inputs | customerRef; lineItems[]; validityDays?; idempotencyKey |
| Expected Output | quotationRef; totals |
| Possible Outcomes | Created / Denied / PendingApproval |
| Failure Scenarios | Invalid items; price below floor |
| Validation Rules | Items exist; prices within policy floors |
| Approval Requirements | Below-floor or custom pricing Yes |
| Human Escalation Rules | Negotiation |
| Audit Events | ToolExecuted |
| Analytics Events | QuotationCreated |
| Business Rules | Cannot invent unpublished prices |
| Security Considerations | — |
| Idempotency Rules | Required |
| Concurrency Considerations | — |
| Side Effects | Quotation persisted |
| Dependencies | SearchProducts, GetPublishedPricing |
| Related Tools | AcceptQuotation, RejectQuotation, NotifyCustomer |
| Versioning Notes | v1 |

---

## AcceptQuotation

| Field | Content |
|-------|---------|
| Tool Name | `AcceptQuotation` |
| Business Purpose | Customer accepts quotation → may create order/reservation downstream |
| Description | Sensitive commercial accept |
| Business Intent(s) | RentDress, AskPrice (accept path) |
| Required Capabilities | `accept_quotation` |
| Required Permissions | Policy grant |
| Allowed Modes | A Approval · H Approval · F Approval typical |
| Required Context | quotationRef; customer confirm |
| Expected Inputs | quotationRef; customerConfirm; approvalToken?; idempotencyKey |
| Expected Output | accepted; optional orderRef |
| Possible Outcomes | Accepted / Expired / Denied / PendingApproval |
| Failure Scenarios | Expired quotation |
| Validation Rules | Not expired; confirm |
| Approval Requirements | Usually Yes |
| Human Escalation Rules | High value |
| Audit Events | ToolExecuted, Approval* |
| Analytics Events | QuotationAccepted |
| Business Rules | Expiry enforced |
| Security Considerations | Customer scope |
| Idempotency Rules | Required |
| Concurrency Considerations | Status transition |
| Side Effects | May create order |
| Dependencies | GenerateQuotation |
| Related Tools | CreateReservation, GetOrderStatus |
| Versioning Notes | v1 |

---

## RejectQuotation

| Field | Content |
|-------|---------|
| Tool Name | `RejectQuotation` |
| Business Purpose | Mark quotation rejected |
| Description | Closes quotation without order |
| Business Intent(s) | AskPrice (decline) |
| Required Capabilities | `reject_quotation` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | quotationRef |
| Expected Inputs | quotationRef; reason? |
| Expected Output | rejected |
| Possible Outcomes | Rejected / AlreadyFinal |
| Failure Scenarios | Missing quotation |
| Validation Rules | Status open |
| Approval Requirements | None typical |
| Human Escalation Rules | None |
| Audit Events | ToolExecuted |
| Analytics Events | QuotationRejected |
| Business Rules | Idempotent reject |
| Security Considerations | Scope |
| Idempotency Rules | Yes |
| Concurrency Considerations | Status guard |
| Side Effects | Quotation closed |
| Dependencies | GenerateQuotation |
| Related Tools | CreateFollowUp |
| Versioning Notes | v1 |

---

## ApplyDiscount *(Critical — deny by default)*

| Field | Content |
|-------|---------|
| Tool Name | `ApplyDiscount` |
| Business Purpose | Apply non-published discount within ceiling |
| Description | Changes commercial terms |
| Business Intent(s) | RequestDiscount |
| Required Capabilities | `apply_discount` |
| Required Permissions | Deny-by-default; ceiling required |
| Allowed Modes | A no silent · H Approval · F Approval |
| Required Context | target invoice/quotation; ceiling |
| Expected Inputs | targetRef; discountValue; reason; approvalToken |
| Expected Output | applied |
| Possible Outcomes | Applied / AboveCeiling / Denied |
| Failure Scenarios | Ceiling breach |
| Validation Rules | Within Money Ceiling VO |
| Approval Requirements | **Always** |
| Human Escalation Rules | Default for any ask beyond published offers |
| Audit Events | Approval*, ToolExecuted |
| Analytics Events | DiscountApplied |
| Business Rules | Cannot change base price list |
| Security Considerations | Critical commercial |
| Idempotency Rules | Required |
| Concurrency Considerations | Target lock |
| Side Effects | Price adjustment |
| Dependencies | ListPublishedOffers, RequestApproval |
| Related Tools | GenerateQuotation |
| Versioning Notes | v1 critical |

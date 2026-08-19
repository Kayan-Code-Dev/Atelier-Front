# Contracts — Customer & Lead Tools

---

## GetCustomerProfile

| # | Field | Contract |
|---|-------|----------|
| 1 | Tool Name | `GetCustomerProfile` |
| 2 | Business Purpose | Load known customer facts for personalization and status journeys |
| 3 | Description | Returns a permission-filtered profile for a Customer Reference |
| 4 | Business Intent(s) | ReturningCustomer, TrackOrder, AskBalance, AskInvoice, ProvideCustomerData |
| 5 | Required Capabilities | `read_customer_profile` |
| 6 | Required Permissions | Same as capabilities via active Capability Policy |
| 7 | Allowed Modes | A (suggest facts to staff) · H · F |
| 8 | Required Context | Tenant Isolation Key; CustomerRef or resolvable Contact |
| 9 | Expected Inputs | `customerRef` **or** `contactIdentifiers` |
| 10 | Expected Output | Name, phones, notes summary, flags (VIP), open-order counts (no secrets) |
| 11 | Possible Outcomes | `Found` · `NotFound` · `Ambiguous` |
| 12 | Failure Scenarios | Ambiguous match; tenant mismatch; profile restricted |
| 13 | Validation Rules | Exactly one identity strategy; Isolation Key required |
| 14 | Approval Requirements | None |
| 15 | Human Escalation Rules | Ambiguous identity → Clarify then Escalate |
| 16 | Audit Events | ToolAuthorized, ToolExecuted/ToolFailed/ToolDenied |
| 17 | Analytics Events | CustomerProfileRead |
| 18 | Business Rules | Never invent a customer; return only allowed fields |
| 19 | Security | PII minimization; no cross-tenant ids |
| 20 | Idempotency | Read-safe; identical inputs → equivalent output |
| 21 | Concurrency | Read committed snapshot; no locks |
| 22 | Side Effects | None |
| 23 | Dependencies | Tenant Ops Customer read port |
| 24 | Related Tools | `SearchCustomer`, `UpsertCustomerProfile` |
| 25 | Versioning | v1 profile fields stable; additive fields in minor versions |

---

## SearchCustomer

| # | Field | Contract |
|---|-------|----------|
| 1 | `SearchCustomer` |
| 2 | Find customer candidates by phone/name/email |
| 3 | Ranked candidate list for disambiguation |
| 4 | TrackOrder, AskInvoice, AskBalance, ChangePhoneNumber |
| 5 | `search_customers` |
| 6 | Capability Policy grant |
| 7 | A · H · F |
| 8 | Isolation Key; search tokens from Conversation |
| 9 | `query` (phone/name/email), optional `limit` |
| 10 | Candidates[{customerRef, displayLabel, matchReason}] |
| 11 | `Matches` · `Empty` · `TooMany` |
| 12 | Invalid query; rate limit |
| 13 | Min query length; normalize phone; max limit |
| 14 | None |
| 15 | TooMany unresolved → Escalate |
| 16 | Tool* audit events |
| 17 | CustomerSearch |
| 18 | Do not auto-pick when >1 strong match |
| 19 | Prevent enumeration abuse (rate limit) |
| 20 | Read-idempotent |
| 21 | Safe concurrent reads |
| 22 | None |
| 23 | Customer search port |
| 24 | `GetCustomerProfile` |
| 25 | v1 |

*(Fields 1–25 in order as columns above for compact tools; full labeled tables for critical tools.)*

### SearchCustomer (labeled)

| Field | Content |
|-------|---------|
| Tool Name | `SearchCustomer` |
| Business Purpose | Disambiguate which customer the Contact refers to |
| Description | Returns ranked candidates; never silently binds wrong customer |
| Business Intent(s) | TrackOrder, AskInvoice, AskBalance, AskDeliveryStatus |
| Required Capabilities | `search_customers` |
| Required Permissions | Policy grant of `search_customers` |
| Allowed Modes | A, H, F |
| Required Context | Isolation Key |
| Expected Inputs | query text/phone/email; optional limit |
| Expected Output | Candidate list with match reasons |
| Possible Outcomes | Matches / Empty / TooMany |
| Failure Scenarios | Malformed phone; throttle |
| Validation Rules | Normalize identifiers; enforce max candidates |
| Approval Requirements | None |
| Human Escalation Rules | Persistent ambiguity |
| Audit Events | ToolExecuted / Denied / Failed |
| Analytics Events | CustomerSearch |
| Business Rules | AI must Clarify if multiple strong matches |
| Security Considerations | Anti-enumeration; PII labels only |
| Idempotency Rules | Read-idempotent |
| Concurrency Considerations | None special |
| Side Effects | None |
| Dependencies | Customer search port |
| Related Tools | GetCustomerProfile |
| Versioning Notes | v1 |

---

## UpsertCustomerProfile

| Field | Content |
|-------|---------|
| Tool Name | `UpsertCustomerProfile` |
| Business Purpose | Create or update basic customer profile from conversation data |
| Description | Bounded write of non-sensitive profile fields |
| Business Intent(s) | ProvideCustomerData, RentDress, BookAppointment |
| Required Capabilities | `write_customer_profile` |
| Required Permissions | Policy grant |
| Allowed Modes | A (draft/approve) · H (often Approval) · F (if granted) |
| Required Context | Isolation Key; Contact; verified fields in Memory |
| Expected Inputs | contact link; name; optional phones/notes; customerRef if update |
| Expected Output | customerRef; created/updated flag |
| Possible Outcomes | Created / Updated / RejectedValidation / Denied |
| Failure Scenarios | Duplicate conflict; policy deny; incomplete required fields |
| Validation Rules | Required name or phone; phone format; no role/permission fields |
| Approval Requirements | H: optional; F: per ceiling; identity merge → Approval |
| Human Escalation Rules | Merge conflicts; KYC-like documents |
| Audit Events | ToolExecuted |
| Analytics Events | CustomerUpserted |
| Business Rules | Does not delete customers; does not change accounting |
| Security Considerations | Prevent takeover via unverified phone overwrite (use UpdateCustomerPhone) |
| Idempotency Rules | Idempotency key recommended for create-from-contact |
| Concurrency Considerations | Optimistic merge on customerRef |
| Side Effects | Customer create/update in Tenant Ops |
| Dependencies | Customer write port |
| Related Tools | SaveCustomerNote, UpdateCustomerPhone, SearchCustomer |
| Versioning Notes | v1 basic fields only |

---

## SaveCustomerNote

| Field | Content |
|-------|---------|
| Tool Name | `SaveCustomerNote` |
| Business Purpose | Attach operational note to customer without structured field change |
| Description | Appends a note from AI/staff summary |
| Business Intent(s) | ProvideCustomerData, Complaint, ReturningCustomer |
| Required Capabilities | `write_customer_notes` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | customerRef |
| Expected Inputs | customerRef; noteText; source=conversationId |
| Expected Output | noteId |
| Possible Outcomes | Saved / Denied |
| Failure Scenarios | Empty note; customer missing |
| Validation Rules | Max length; strip secrets if policy |
| Approval Requirements | None typical |
| Human Escalation Rules | None |
| Audit Events | ToolExecuted |
| Analytics Events | CustomerNoteSaved |
| Business Rules | Append-only preferred |
| Security Considerations | No password/card data in notes |
| Idempotency Rules | Optional idempotency key to avoid dup notes |
| Concurrency Considerations | Append safe |
| Side Effects | Note written |
| Dependencies | Customer notes port |
| Related Tools | UpsertCustomerProfile |
| Versioning Notes | v1 |

---

## UpdateCustomerPhone

| Field | Content |
|-------|---------|
| Tool Name | `UpdateCustomerPhone` |
| Business Purpose | Change customer phone with identity safeguards |
| Description | Sensitive identity write |
| Business Intent(s) | ChangePhoneNumber |
| Required Capabilities | `update_customer_phone` |
| Required Permissions | Policy grant (rarely enabled) |
| Allowed Modes | A (never auto) · H (Approval+Human default) · F (Approval required) |
| Required Context | Strong customerRef; verification evidence |
| Expected Inputs | customerRef; newPhone; verificationEvidence |
| Expected Output | updated flag |
| Possible Outcomes | Updated / PendingApproval / Denied / Escalated |
| Failure Scenarios | Weak identity; phone in use by another customer |
| Validation Rules | E.164/normalized phone; evidence required |
| Approval Requirements | **Always** unless break-glass staff tool path |
| Human Escalation Rules | Default Hybrid escalate |
| Audit Events | ApprovalRequested, ToolExecuted |
| Analytics Events | PhoneChangeAttempt |
| Business Rules | Channel Contact id change ≠ silent profile phone change |
| Security Considerations | Account-takeover prevention |
| Idempotency Rules | Same newPhone+customer → no-op success |
| Concurrency Considerations | Lock customer identity record conceptually |
| Side Effects | Phone updated; may affect future Contact match |
| Dependencies | Customer identity port |
| Related Tools | SearchCustomer, RequestApproval |
| Versioning Notes | v1 sensitive |

---

## CreateLeadNote / UpdateLeadNote

| Field | Content |
|-------|---------|
| Tool Name | `CreateLeadNote` / `UpdateLeadNote` |
| Business Purpose | Capture pre-customer interest inside Agent Platform / Ops lead store |
| Description | Lead interest record for sales follow-up (not Platform CRM unless bridged) |
| Business Intent(s) | RentDress, AskPrice, AskAvailability, ReturningCustomer |
| Required Capabilities | `write_lead_notes` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | Contact; Conversation |
| Expected Inputs | contact; interest summary; optional product refs |
| Expected Output | leadNoteId |
| Possible Outcomes | Created / Updated / Denied |
| Failure Scenarios | Empty interest |
| Validation Rules | Max size; tenant scope |
| Approval Requirements | None |
| Human Escalation Rules | High-value VIP → optional NotifyStaff |
| Audit Events | ToolExecuted |
| Analytics Events | LeadCaptured |
| Business Rules | Does not create paid orders |
| Security Considerations | Tenant isolation |
| Idempotency Rules | Create with conversation-interest key |
| Concurrency Considerations | Last-write-wins on Update |
| Side Effects | Lead note persisted |
| Dependencies | Lead/interest port |
| Related Tools | CreateFollowUp, NotifyStaff |
| Versioning Notes | v1 |

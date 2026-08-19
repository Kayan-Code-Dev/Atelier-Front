# Catalog — G10 Complaints · G11 Identity · G12 Media · G13 Human · G14 Automation · G15 Admin

---

## UC-CMP-01 — Customer Complaint

| Field | Content |
|-------|---------|
| **Goal** | Empathize, capture issue, escalate to human; avoid uncommitted remedies. |
| **Actors** | Customer, AI Agent, Human Staff |
| **Preconditions** | Active Conversation |
| **Trigger** | Complaint / anger / quality issue |
| **Main Success Flow** | Acknowledge → classify topic → gather facts → open Task + HumanHandover → notify staff → optional holding reply |
| **Alternative Flows** | Simple FAQ misunderstanding → resolve via Knowledge then confirm satisfaction |
| **Failure Scenarios** | AI promises refund/discount → Forbidden without capability |
| **Required Context** | Customer history; related orders; Persona empathy rules |
| **Required Business Tools** | `CreateStaffTask`; optional order lookups |
| **Required Permissions** | `reply_to_customers`; `create_internal_task`; not refund/delete |
| **Expected Output** | Empathy + escalation notice |
| **Conversation Outcome** | `ComplaintOpened` / `WaitingHuman` |
| **Audit Events** | HumanHandoverStarted, TaskCreated |
| **Analytics Events** | ComplaintOpened |
| **Approval?** | For any remedy |
| **Handover?** | **Yes (default)** |
| **Confidence** | Escalate on Low/Medium emotion risk |

---

## UC-CMP-02 — Request Discount

| Field | Content |
|-------|---------|
| **Goal** | Handle discount ask without unauthorized price changes. |
| **Actors** | Customer, AI Agent, Sales |
| **Preconditions** | Pricing policy known |
| **Trigger** | Ask discount / cheaper |
| **Main Success Flow** | Explain published offers only → if insists, Escalation/Approval; never change base prices |
| **Alternative Flows** | Within allowed ceiling & capability → ApprovalRequest then apply tool |
| **Failure Scenarios** | Agent invents discount → Forbidden |
| **Required Context** | Offers; ceilings; Customer value signals |
| **Required Business Tools** | `ListPublishedOffers`; optional `ApplyDiscount` (sensitive) |
| **Required Permissions** | `read_offers`; `apply_discount` rarely |
| **Expected Output** | Offer info or handover |
| **Conversation Outcome** | `WaitingHuman` / `LeadQualified` |
| **Audit Events** | ApprovalRequested / HumanHandoverStarted |
| **Analytics Events** | DiscountRequested |
| **Approval?** | **Yes** for any non-published discount |
| **Handover?** | **Yes** typical Hybrid |
| **Confidence** | High only for published offers |

---

## UC-CMP-03 — Request Exception / Manager

| Field | Content |
|-------|---------|
| **Goal** | Route exception or manager request to humans. |
| **Actors** | Customer, AI Agent, Human Staff |
| **Preconditions** | — |
| **Trigger** | Want manager / exception / special treatment |
| **Main Success Flow** | Acknowledge → Handover with reason `StaffForceTake`/`PolicyException` → notify |
| **Alternative Flows** | None for Full Auto financial exceptions |
| **Failure Scenarios** | AI grants exception → Forbidden |
| **Required Context** | Summary packet |
| **Required Business Tools** | `CreateStaffTask` |
| **Required Permissions** | escalate path |
| **Expected Output** | Human will continue |
| **Conversation Outcome** | `WaitingHuman` |
| **Audit Events** | HumanHandoverStarted |
| **Analytics Events** | ManagerRequested |
| **Approval?** | Human decides |
| **Handover?** | **Yes** |
| **Confidence** | N/A |

---

## UC-ID-01 — Customer Sends Personal Data

| Field | Content |
|-------|---------|
| **Goal** | Capture identity data into Memory / Customer notes within privacy rules. |
| **Actors** | Customer, AI Agent |
| **Preconditions** | `write_customer_notes` or create customer if allowed |
| **Trigger** | Shares name/address/sizes/data |
| **Main Success Flow** | Confirm what was captured → `UpsertCustomerProfile` or Memory-only → thank + next step |
| **Alternative Flows** | Insufficient permission → Memory only + staff Task |
| **Failure Scenarios** | Over-collection sensitive IDs → refuse & explain |
| **Required Context** | Contact; existing CustomerRef |
| **Required Business Tools** | `UpsertCustomerProfile`; `SaveCustomerNote` |
| **Required Permissions** | `write_customer_profile` / `write_customer_notes` |
| **Expected Output** | Confirmation of saved fields |
| **Conversation Outcome** | `LeadQualified` / `WaitingCustomer` |
| **Audit Events** | ToolExecuted |
| **Analytics Events** | CustomerDataCaptured |
| **Approval?** | No for basic profile |
| **Handover?** | If legal/KYC-like docs |
| **Confidence** | Medium+ |

---

## UC-ID-02 — Change Phone Number

| Field | Content |
|-------|---------|
| **Goal** | Update customer phone carefully (identity risk). |
| **Actors** | Customer, AI Agent, Human Staff |
| **Preconditions** | Strong identity or human |
| **Trigger** | Change my phone |
| **Main Success Flow** | Verify identity signals → if weak Escalate → else Approval/`UpdateCustomerPhone` |
| **Alternative Flows** | Channel contact id differs → always Escalate |
| **Failure Scenarios** | Account takeover risk → Block/Escalate |
| **Required Context** | Customer; Contact ids |
| **Required Business Tools** | `UpdateCustomerPhone` |
| **Required Permissions** | `update_customer_phone` (sensitive) |
| **Expected Output** | Updated or human verification |
| **Conversation Outcome** | `WaitingHuman` / `SupportSolved` |
| **Audit Events** | Approval*/Handover*/ToolExecuted |
| **Analytics Events** | PhoneChangeRequest |
| **Approval?** | **Yes** |
| **Handover?** | Default Hybrid |
| **Confidence** | High + verification |

---

## UC-MED-01 — Customer Sends Dress Image

| Field | Content |
|-------|---------|
| **Goal** | Accept image, acknowledge, use for qualification; optional vision later. |
| **Actors** | Customer, AI Agent, Sales |
| **Preconditions** | Channel supports image |
| **Trigger** | Image message |
| **Main Success Flow** | Ack → store media ref on Message → ask clarifying questions → link to rental/tailoring flow |
| **Alternative Flows** | If catalog match tool exists → suggest similar; else Escalate for styling |
| **Failure Scenarios** | Unsafe image → Block path |
| **Required Context** | Media metadata; Memory |
| **Required Business Tools** | Optional `MatchCatalogByImage` (future); `CreateStaffTask` |
| **Required Permissions** | `reply_to_customers`; optional match tool |
| **Expected Output** | Ack + questions / options |
| **Conversation Outcome** | `LeadQualified` / `WaitingHuman` |
| **Audit Events** | MessageReceived, AIResponseGenerated |
| **Analytics Events** | MediaImageReceived |
| **Approval?** | No for ack |
| **Handover?** | Styling consult |
| **Confidence** | Low for auto-match without tool → Clarify |

---

## UC-MED-02 — Customer Sends Voice Note

| Field | Content |
|-------|---------|
| **Goal** | Handle voice: transcribe if available, else ask text or escalate. |
| **Actors** | Customer, AI Agent, System |
| **Preconditions** | Voice inbound |
| **Trigger** | Audio message |
| **Main Success Flow** | If transcription provider enabled → transcript → normal intent flow; else ask typed text or Handover |
| **Alternative Flows** | Low transcript confidence → Clarify |
| **Failure Scenarios** | No STT → do not invent content |
| **Required Context** | Transcript as Message Content |
| **Required Business Tools** | `TranscribeAudio` (optional platform service) |
| **Required Permissions** | `reply_to_customers` |
| **Expected Output** | Continue as text intent or human |
| **Conversation Outcome** | Depends on resulting intent |
| **Audit Events** | MessageReceived |
| **Analytics Events** | MediaVoiceReceived |
| **Approval?** | No |
| **Handover?** | If cannot process |
| **Confidence** | Bound to transcript confidence |

---

## UC-MED-03 — Customer Sends PDF

| Field | Content |
|-------|---------|
| **Goal** | Accept PDF (often payment/measurement); route correctly. |
| **Actors** | Customer, AI Agent, Human Staff |
| **Preconditions** | Document inbound |
| **Trigger** | PDF/document |
| **Main Success Flow** | Ack → classify likely type (receipt vs form) → UC-PAY-02 or staff Task → do not auto-execute financial tools |
| **Alternative Flows** | Readable text extract → assist classification |
| **Failure Scenarios** | Malware/unsafe → reject per safety |
| **Required Context** | Media; open invoices |
| **Required Business Tools** | `RegisterPaymentProof` / `CreateStaffTask` |
| **Required Permissions** | proof/task caps |
| **Expected Output** | Ack + next step |
| **Conversation Outcome** | `WaitingHuman` / `FollowUpRequired` |
| **Audit Events** | MessageReceived, TaskCreated |
| **Analytics Events** | MediaPdfReceived |
| **Approval?** | Before financial effect |
| **Handover?** | Typical |
| **Confidence** | Low auto-class → Clarify/Escalate |

---

## UC-HUM-01 — Customer Requests Human

| Field | Content |
|-------|---------|
| **Goal** | Immediate Human Handover. |
| **Actors** | Customer, AI Agent, Human Staff |
| **Preconditions** | — |
| **Trigger** | Talk to person/employee |
| **Main Success Flow** | Confirm → HumanHandoverStarted → notify → holding message |
| **Alternative Flows** | Off hours → set expectation + Task |
| **Failure Scenarios** | No staff online → queue Task, keep WaitingHuman |
| **Required Context** | Summary packet |
| **Required Business Tools** | `CreateStaffTask` |
| **Required Permissions** | escalate always allowed for this intent |
| **Expected Output** | Human will continue |
| **Conversation Outcome** | `WaitingHuman` |
| **Audit Events** | HumanHandoverStarted, NotificationEmitted |
| **Analytics Events** | ExplicitHumanRequest |
| **Approval?** | No |
| **Handover?** | **Yes** |
| **Confidence** | High (explicit) |

---

## UC-HUM-02 — Staff Takes Over / Returns to AI

| Field | Content |
|-------|---------|
| **Goal** | Support ownership changes initiated by staff. |
| **Actors** | Human Staff, System, AI Agent |
| **Preconditions** | Active Conversation |
| **Trigger** | Staff force-take or Return to AI |
| **Main Success Flow** | Update Ownership → finish/start Handover → Audit → optional customer notice |
| **Alternative Flows** | Return to AI with unresolved approval → block until decided |
| **Failure Scenarios** | Return while Blocked → reject |
| **Required Context** | Handover state; pending Approvals |
| **Required Business Tools** | None domain; collaboration desk |
| **Required Permissions** | Staff auth (Tenant Ops) |
| **Expected Output** | Ownership change |
| **Conversation Outcome** | `WaitingHuman` / `WaitingCustomer` |
| **Audit Events** | ConversationAssigned, HumanHandoverFinished |
| **Analytics Events** | OwnershipChanged |
| **Approval?** | N/A |
| **Handover?** | Definitional |
| **Confidence** | N/A |

---

## UC-AUTO-01 — Appointment Reminder

| Field | Content |
|-------|---------|
| **Goal** | Remind customer before fitting/delivery. |
| **Actors** | System, AI Agent, Customer |
| **Preconditions** | Workflow Active; consent/policy ok |
| **Trigger** | TimeSchedule before appointment |
| **Main Success Flow** | Load appointment → send reminder → await confirm/reschedule intents |
| **Alternative Flows** | Quiet hours → delay send |
| **Failure Scenarios** | Send fail → staff Notification |
| **Required Context** | Appointment facts |
| **Required Business Tools** | `GetAppointment` |
| **Required Permissions** | `reply_to_customers` (outbound automation) |
| **Expected Output** | Reminder message |
| **Conversation Outcome** | `WaitingCustomer` / `FollowUpRequired` |
| **Audit Events** | WorkflowTriggered, MessageSent |
| **Analytics Events** | ReminderSent |
| **Approval?** | No |
| **Handover?** | No |
| **Confidence** | N/A |

---

## UC-AUTO-02 — Post-Service Feedback / Follow-up

| Field | Content |
|-------|---------|
| **Goal** | Ask satisfaction; open complaint path if negative. |
| **Actors** | System, AI Agent, Customer, Human Staff |
| **Preconditions** | Order completed |
| **Trigger** | DomainEvent order completed + delay |
| **Main Success Flow** | Send short feedback ask → if negative → UC-CMP-01 |
| **Alternative Flows** | No reply → UC-HYG-04 |
| **Failure Scenarios** | Opt-out → stop workflow |
| **Required Context** | Order outcome |
| **Required Business Tools** | None / Task on negative |
| **Required Permissions** | outbound reply automation |
| **Expected Output** | Feedback prompt |
| **Conversation Outcome** | `SupportSolved` / `ComplaintOpened` |
| **Audit Events** | WorkflowTriggered |
| **Analytics Events** | FeedbackPromptSent |
| **Approval?** | No |
| **Handover?** | On negative |
| **Confidence** | N/A |

---

## UC-ADM-01 — Admin Updates Knowledge Affecting Answers

| Field | Content |
|-------|---------|
| **Goal** | Ensure published knowledge changes apply to future Context. |
| **Actors** | Admin, System |
| **Preconditions** | Admin rights |
| **Trigger** | KnowledgeUpdated |
| **Main Success Flow** | Publish document → invalidate knowledge provider cache conceptually → subsequent UCs use new facts |
| **Alternative Flows** | Draft not published → no customer effect |
| **Failure Scenarios** | Conflicting docs → retrieval ranking / admin fix |
| **Required Context** | N/A runtime customer |
| **Required Business Tools** | Knowledge admin (outside customer tools) |
| **Required Permissions** | Admin knowledge publish |
| **Expected Output** | Updated atelier brain |
| **Conversation Outcome** | N/A |
| **Audit Events** | KnowledgeUpdated |
| **Analytics Events** | KnowledgePublish |
| **Approval?** | Per admin process |
| **Handover?** | N/A |
| **Confidence** | N/A |

---

## UC-ADM-02 — Admin Changes Capability Policy / Mode

| Field | Content |
|-------|---------|
| **Goal** | Change what Agent may do / autonomy overlay. |
| **Actors** | Admin, System |
| **Preconditions** | Agent exists |
| **Trigger** | Policy/Mode change |
| **Main Success Flow** | Activate revision → CapabilityPolicyRevised / OperatingModeChanged → future decision cycles use new overlay |
| **Alternative Flows** | In-flight ToolExecution keeps ticket snapshot |
| **Failure Scenarios** | Mode used to expand permissions → Forbidden (invariant) |
| **Required Context** | N/A |
| **Required Business Tools** | Admin policy |
| **Required Permissions** | Admin |
| **Expected Output** | New agent behavior bounds |
| **Conversation Outcome** | N/A |
| **Audit Events** | CapabilityPolicyRevised, OperatingModeChanged |
| **Analytics Events** | PolicyChange |
| **Approval?** | Admin governance |
| **Handover?** | N/A |
| **Confidence** | N/A |

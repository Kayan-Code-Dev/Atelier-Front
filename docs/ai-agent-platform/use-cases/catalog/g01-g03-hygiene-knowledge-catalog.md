# Catalog — G01 Conversation Hygiene · G02 Atelier Knowledge · G03 Catalog

Shared fields legend: **Approval?** / **Handover?** / **Confidence** appear at end of each UC.

---

## UC-HYG-01 — Customer Greets

| Field | Content |
|-------|---------|
| **Goal** | Acknowledge customer and invite how the atelier can help. |
| **Actors** | Customer, AI Agent, System |
| **Preconditions** | Channel Account Active; Agent Active; Conversation New or ActiveAI |
| **Trigger** | Inbound greeting message |
| **Main Success Flow** | 1) Resolve Tenant 2) Open/continue Conversation 3) Intent=`Greeting` 4) Assemble light Context (persona, hours snippet) 5) Reply welcome + soft CTA 6) Await customer |
| **Alternative Flows** | Returning known Customer → personalized welcome using CustomerRef name |
| **Failure Scenarios** | Binding fail → no reply to wrong tenant; Agent Paused → System notice or escalate per policy |
| **Required Context** | Persona; optional CustomerRef; Business Hours |
| **Required Business Tools** | Optional `LookupCustomerByContact` |
| **Required Permissions** | `reply_to_customers` |
| **Expected Output** | Friendly greeting in atelier language |
| **Conversation Outcome** | `WaitingCustomer` |
| **Audit Events** | MessageReceived, AIResponseGenerated |
| **Analytics Events** | ConversationStarted (if new), IntentDetected=Greeting |
| **Approval?** | No |
| **Handover?** | No |
| **Confidence** | Medium+ (greeting patterns); else Clarify |

---

## UC-HYG-02 — Customer Says Goodbye

| Field | Content |
|-------|---------|
| **Goal** | Close politely; mark resolved if no open tasks. |
| **Actors** | Customer, AI Agent, System |
| **Preconditions** | Active Conversation |
| **Trigger** | Goodbye / thanks closing |
| **Main Success Flow** | 1) Intent=`Goodbye` 2) Check open Tasks/Approvals/Handover 3) If none → farewell reply 4) Transition toward Resolved 5) Emit summary if policy |
| **Alternative Flows** | Open complaint/task → thank + keep open / notify staff |
| **Failure Scenarios** | Ownership=Human → AI does not auto-close; staff owns |
| **Required Context** | Memory open tasks; Ownership |
| **Required Business Tools** | None |
| **Required Permissions** | `reply_to_customers` |
| **Expected Output** | Farewell; optional resolve |
| **Conversation Outcome** | `SupportSolved` or `FollowUpRequired` |
| **Audit Events** | AIResponseGenerated, ConversationStateChanged |
| **Analytics Events** | ConversationResolved (if closed) |
| **Approval?** | No |
| **Handover?** | Only if open sensitive task |
| **Confidence** | Medium |

---

## UC-HYG-03 — Small Talk

| Field | Content |
|-------|---------|
| **Goal** | Respond briefly without derailing business help. |
| **Actors** | Customer, AI Agent |
| **Preconditions** | ActiveAI |
| **Trigger** | Chit-chat without business ask |
| **Main Success Flow** | Short persona-aligned reply + redirect to services |
| **Alternative Flows** | Repeated off-topic → gentle boundary; if Mode=Assistant → suggest to staff |
| **Failure Scenarios** | Unsafe content → Safety escalate |
| **Required Context** | Persona Do/Don’t |
| **Required Business Tools** | None |
| **Required Permissions** | `reply_to_customers` |
| **Expected Output** | Brief reply + CTA |
| **Conversation Outcome** | `WaitingCustomer` |
| **Audit Events** | AIResponseGenerated |
| **Analytics Events** | IntentDetected=SmallTalk |
| **Approval?** | No |
| **Handover?** | If safety |
| **Confidence** | Medium |

---

## UC-HYG-04 — Customer Does Not Reply (Silence)

| Field | Content |
|-------|---------|
| **Goal** | Follow up or snooze/resolve per Automation Workflow. |
| **Actors** | System, AI Agent, Human Staff (optional) |
| **Preconditions** | AwaitingCustomer beyond SLA |
| **Trigger** | Timer / AutomationTrigger=`SLABreach` or schedule |
| **Main Success Flow** | 1) Workflow fires 2) If allowed → polite follow-up message 3) Else create Task for staff 4) Optionally Snooze |
| **Alternative Flows** | Max follow-ups reached → Resolve Abandoned or escalate |
| **Failure Scenarios** | Channel send fail → Notification to staff |
| **Required Context** | Last intent; open objects; quiet hours |
| **Required Business Tools** | None (message only) or `CreateStaffTask` |
| **Required Permissions** | `reply_to_customers`; `create_internal_task` |
| **Expected Output** | Follow-up or staff Task |
| **Conversation Outcome** | `FollowUpRequired` / `WaitingCustomer` / `LeadLost` |
| **Audit Events** | WorkflowTriggered, MessageSent or TaskCreated |
| **Analytics Events** | SilenceFollowUpSent |
| **Approval?** | No (unless policy caps outreach) |
| **Handover?** | Optional after N attempts |
| **Confidence** | N/A (system) |

---

## UC-HYG-05 — Customer Returns After Long Gap

| Field | Content |
|-------|---------|
| **Goal** | Re-orient with summary; avoid assuming old open booking still valid. |
| **Actors** | Customer, AI Agent, System |
| **Preconditions** | Prior Conversation Closed/Resolved or long Snoozed |
| **Trigger** | New inbound after inactivity threshold |
| **Main Success Flow** | 1) Start/reopen Conversation per policy 2) Load CustomerRef + last Summary 3) Confirm whether continuing old topic 4) Clarify next need |
| **Alternative Flows** | Stale open order → refresh via TrackOrder tools before advising |
| **Failure Scenarios** | Conflicting old intents → Clarify or Escalate |
| **Required Context** | Prior Summary; Customer; current open Business Objects |
| **Required Business Tools** | `LookupCustomerByContact`; optional `ListOpenOrdersForCustomer` |
| **Required Permissions** | `reply_to_customers`; `read_order_status` |
| **Expected Output** | Re-engagement clarify prompt |
| **Conversation Outcome** | `WaitingCustomer` / `LeadQualified` |
| **Audit Events** | ConversationStarted/Reopened, ContextBundleAssembled |
| **Analytics Events** | ReturningCustomerContact |
| **Approval?** | No |
| **Handover?** | If conflict/high-value stale deal |
| **Confidence** | Medium; ask clarify if Low |

---

## UC-KNOW-01 — Ask Working Hours

| Field | Content |
|-------|---------|
| **Goal** | Provide accurate business hours. |
| **Actors** | Customer, AI Agent |
| **Preconditions** | Knowledge/Settings published |
| **Trigger** | Ask hours / open now? |
| **Main Success Flow** | Context from Settings/KB → Reply hours (+ branch if multi) |
| **Alternative Flows** | Branch-specific hours unknown → Clarify branch or Escalate |
| **Failure Scenarios** | Missing KB → Escalate or say unavailable + handover |
| **Required Context** | Business Hours; Timezone; Persona |
| **Required Business Tools** | Optional `GetAtelierSettings` |
| **Required Permissions** | `reply_to_customers`; `read_atelier_settings` |
| **Expected Output** | Hours text |
| **Conversation Outcome** | `SupportSolved` / `WaitingCustomer` |
| **Audit Events** | AIResponseGenerated |
| **Analytics Events** | KnowledgeIntentServed=Hours |
| **Approval?** | No |
| **Handover?** | Only if data missing |
| **Confidence** | High if KB present |

---

## UC-KNOW-02 — Share Location / Address

| Field | Content |
|-------|---------|
| **Goal** | Send atelier address/map guidance. |
| **Actors** | Customer, AI Agent |
| **Preconditions** | Address in Settings/KB |
| **Trigger** | Ask location / address / map |
| **Main Success Flow** | Reply address + landmarks; channel-native location if supported later |
| **Alternative Flows** | Multi-branch → ask which branch |
| **Failure Scenarios** | No address configured → Escalate |
| **Required Context** | Branch settings; Language |
| **Required Business Tools** | `GetAtelierSettings` |
| **Required Permissions** | `reply_to_customers`; `read_atelier_settings` |
| **Expected Output** | Address message |
| **Conversation Outcome** | `SupportSolved` |
| **Audit Events** | AIResponseGenerated |
| **Analytics Events** | KnowledgeIntentServed=Location |
| **Approval?** | No |
| **Handover?** | If missing |
| **Confidence** | High |

---

## UC-KNOW-03 — Ask Cancellation Policy

| Field | Content |
|-------|---------|
| **Goal** | Explain cancellation/refund policy from Knowledge (not invent terms). |
| **Actors** | Customer, AI Agent |
| **Preconditions** | Policy document Published |
| **Trigger** | Ask cancel policy / refund rules |
| **Main Success Flow** | Retrieve KB policy → Reply with citation-backed summary → Offer human if dispute |
| **Alternative Flows** | Customer disputes past charge → Escalate (not AI legal advice) |
| **Failure Scenarios** | No policy doc → Escalate |
| **Required Context** | Knowledge policy; Persona limits |
| **Required Business Tools** | Knowledge retrieval (provider); no financial mutate |
| **Required Permissions** | `reply_to_customers`; `read_knowledge` |
| **Expected Output** | Policy explanation |
| **Conversation Outcome** | `SupportSolved` / `WaitingHuman` if dispute |
| **Audit Events** | AIResponseGenerated, KnowledgeCited |
| **Analytics Events** | PolicyInquiry |
| **Approval?** | No |
| **Handover?** | On dispute / Low confidence |
| **Confidence** | High for published policy; else Escalate |

---

## UC-KNOW-04 — Ask Seasonal Offers

| Field | Content |
|-------|---------|
| **Goal** | Share current published offers only. |
| **Actors** | Customer, AI Agent |
| **Preconditions** | Offers in KB or catalog promotions |
| **Trigger** | Ask offers / discounts / season promo |
| **Main Success Flow** | Load published offers → Reply; do not invent discounts |
| **Alternative Flows** | Customer asks custom discount → route UC-CMP-02 |
| **Failure Scenarios** | Stale offer → refuse + Escalate/Admin refresh |
| **Required Context** | Knowledge offers; date validity |
| **Required Business Tools** | `ListPublishedOffers` (if exists) / KB |
| **Required Permissions** | `reply_to_customers`; `read_offers` |
| **Expected Output** | Offer list + CTA |
| **Conversation Outcome** | `LeadQualified` / `WaitingCustomer` |
| **Audit Events** | AIResponseGenerated |
| **Analytics Events** | OfferInquiry |
| **Approval?** | No for publishing known offers; Yes for custom discount |
| **Handover?** | Custom discount path |
| **Confidence** | High if published |

---

## UC-CAT-01 — Ask Available Products / Dresses

| Field | Content |
|-------|---------|
| **Goal** | Show availability matching customer need. |
| **Actors** | Customer, AI Agent |
| **Preconditions** | `read_catalog_availability` allowed |
| **Trigger** | Ask what’s available / show dresses |
| **Main Success Flow** | Clarify occasion/date/size if needed → `SearchAvailability` → Summarize options → Soft CTA book/rent |
| **Alternative Flows** | Too many results → ask filters; none → suggest alternatives or waitlist via staff |
| **Failure Scenarios** | Tool fail → apologize + Escalate |
| **Required Context** | Customer prefs in Memory; date if rental |
| **Required Business Tools** | `SearchAvailability`; optional `LookupCustomerByContact` |
| **Required Permissions** | `reply_to_customers`; `read_catalog_availability` |
| **Expected Output** | Short list of available items |
| **Conversation Outcome** | `LeadQualified` / `WaitingCustomer` |
| **Audit Events** | ToolExecuted, AIResponseGenerated |
| **Analytics Events** | CatalogSearch |
| **Approval?** | No |
| **Handover?** | On repeated failure or VIP high-touch policy |
| **Confidence** | Medium+ before search; High after tool facts |

---

## UC-CAT-02 — Check Specific Item Availability

| Field | Content |
|-------|---------|
| **Goal** | Confirm if a named/code dress is free for a date. |
| **Actors** | Customer, AI Agent |
| **Preconditions** | Item identifiable |
| **Trigger** | Ask if dress X available on date Y |
| **Main Success Flow** | Resolve product → `CheckItemAvailability` → Reply yes/no + next step |
| **Alternative Flows** | Ambiguous item → Clarify; unavailable → suggest similar |
| **Failure Scenarios** | Tool deny/fail → Escalate |
| **Required Context** | Product pointer; date; branch |
| **Required Business Tools** | `ResolveProduct`; `CheckItemAvailability` |
| **Required Permissions** | `read_catalog_availability` |
| **Expected Output** | Availability answer |
| **Conversation Outcome** | `WaitingCustomer` / `Appointment` path |
| **Audit Events** | ToolExecuted |
| **Analytics Events** | AvailabilityCheck |
| **Approval?** | No |
| **Handover?** | If customer insists on hold without capability |
| **Confidence** | High after tool |

---

## UC-CAT-03 — Ask Required Sizes / Measurements

| Field | Content |
|-------|---------|
| **Goal** | Explain measurement requirements for rental/tailoring. |
| **Actors** | Customer, AI Agent |
| **Preconditions** | Size guide in KB |
| **Trigger** | Ask sizes / measurements needed |
| **Main Success Flow** | Reply guide from KB; optionally capture sizes into Memory (not Ops write unless permitted) |
| **Alternative Flows** | Tailoring complex → offer appointment UC-APPT-01 |
| **Failure Scenarios** | No guide → Escalate |
| **Required Context** | KB size guide; service type |
| **Required Business Tools** | Optional `SaveCustomerNote` if allowed |
| **Required Permissions** | `read_knowledge`; optional `write_customer_notes` |
| **Expected Output** | Size instructions |
| **Conversation Outcome** | `SupportSolved` / `WaitingCustomer` |
| **Audit Events** | AIResponseGenerated |
| **Analytics Events** | SizeGuideServed |
| **Approval?** | No |
| **Handover?** | Complex bridal fitting advice |
| **Confidence** | High for KB; Medium for advice depth |

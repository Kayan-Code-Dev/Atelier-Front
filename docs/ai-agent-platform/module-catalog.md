# Module Catalog — AI Agent Platform

Each module lists: goal, responsibility, inputs, outputs, relationships, and independence.

---

## 2.1 Channel Adapter Layer (Communication Channels)

| Field | Description |
|-------|-------------|
| **Goal** | Normalize every channel into `NormalizedInboundMessage` / `NormalizedOutboundMessage` |
| **Responsibility** | Webhook verify, provider signatures, media transform, send reply, delivery status (sent/delivered/read/fail) |
| **Inputs** | Provider webhook/payload |
| **Outputs** | Normalized message + Channel Envelope (`channel_type`, `channel_account_id`, `external_thread_id`, `external_contact_id`) |
| **Relationships** | Feeds Ingress only; must not know Tenant Domain |
| **Independence** | Fully independent behind Port `ChannelPort` |

Future adapters (same Port): WhatsApp, Facebook Messenger, Instagram, Website Live Chat, Mobile App Chat, Telegram, Email.

---

## 2.2 Ingress Gateway + Identity Binding

| Field | Description |
|-------|-------------|
| **Goal** | Single secure entry for all channels |
| **Responsibility** | Rate limit, replay protection, signature checks, intake queue, Idempotency key |
| **Inputs** | Normalized message from Adapter |
| **Outputs** | Job/Event ready for Tenant Resolver |
| **Depends on** | Channel Adapters |
| **Independence** | Relatively independent Edge Service |

---

## 2.3 Tenant Resolver & Isolation Guard

| Field | Description |
|-------|-------------|
| **Goal** | Bind message to the correct Tenant; block cross-tenant leakage |
| **Responsibility** | Resolve `tenant_id` from channel binding; enforce session bounds; reject unbound messages |
| **Inputs** | `channel_account_id` / `phone_number_id` / `page_id` / `inbox_id` |
| **Outputs** | `TenantBinding` + logical Isolation Token for the pipeline |
| **Depends on** | Channel Binding Registry (Central / Agent Registry) |
| **Independence** | Security-critical — nothing downstream runs without it |

Detail: [tenant-resolver.md](./tenant-resolver.md).

---

## 2.4 Conversation Manager

| Field | Description |
|-------|-------------|
| **Goal** | Conversation lifecycle as an operational unit |
| **Responsibility** | Create/retrieve Conversation, contact link, thread merge, Ownership (AI/Human), SLA timers |
| **Inputs** | TenantBinding + NormalizedInboundMessage |
| **Outputs** | Conversation Session + Message Log pointer |
| **Depends on** | Tenant Resolver, State Machine |
| **Independence** | Central — source of truth for conversations |

---

## 2.5 Conversation State Machine

| Field | Description |
|-------|-------------|
| **Goal** | Formal, auditable conversation states |
| **Responsibility** | Legal transitions only; fire automation hooks |
| **Inputs** | Events (`message_received`, `tool_succeeded`, `escalate`, `resolve`, …) |
| **Outputs** | New state + side-effect hooks |
| **Depends on** | Conversation Manager |
| **Independence** | Independent state-rules engine |

Detail: [conversation-state-machine.md](./conversation-state-machine.md).

---

## 2.6 Contact & Identity Resolver (Customer Twin)

| Field | Description |
|-------|-------------|
| **Goal** | Map channel sender to atelier customer |
| **Responsibility** | Match by phone/channel id; provisional Lead/Customer within permissions; identity merge |
| **Inputs** | External contact identifiers |
| **Outputs** | `CustomerRef` or `UnknownContact` |
| **Depends on** | Tenant Customers domain |
| **Independence** | Semi-independent via Customer Service Port |

---

## 2.7 AI Orchestrator (Employee Brain)

| Field | Description |
|-------|-------------|
| **Goal** | Digital employee decision: understand → plan → tools → reply or hand over |
| **Responsibility** | Intent classification, Plan/Act, Context request, Mode + Permissions, Handover choice |
| **Inputs** | Conversation snapshot + Context pack + Policies |
| **Outputs** | Decision Record (`reply` \| `tool_calls` \| `escalate` \| `defer`) |
| **Depends on** | Context Engine, Permission Engine, Tool Executor, Handover, Memory |
| **Independence** | Platform core — channel-agnostic |

---

## 2.8 AI Context Engine

| Field | Description |
|-------|-------------|
| **Goal** | Build one context pack before any model call |
| **Responsibility** | Selective retrieval + structured facts, compression, priority ordering, redact out-of-permission data |
| **Inputs** | Conversation + Contact + Policies + Retrieval queries |
| **Outputs** | Unified `AgentContextBundle` |
| **Depends on** | Memory, KB, Business Read Ports, Settings, Persona |
| **Independence** | Independent aggregation layer |

Detail: [context-engine.md](./context-engine.md).

---

## 2.9 Conversation Memory

| Field | Description |
|-------|-------------|
| **Goal** | Short/medium-term memory for conversation and customer |
| **Responsibility** | Summaries, extracted facts, preferences, last intent, open tasks |
| **Inputs** | Messages + tool results |
| **Outputs** | Memory slices for Context Engine |
| **Depends on** | Conversation Manager |
| **Independence** | Storage-independent within Tenant bounds |

---

## 2.10 Knowledge Base (Atelier Brain)

| Field | Description |
|-------|-------------|
| **Goal** | Unstructured knowledge: policies, general pricing, FAQ, tone, owner instructions |
| **Responsibility** | Documents, versions, retrieval, publish authority |
| **Inputs** | Training content / owner docs |
| **Outputs** | Snippets with internal citations |
| **Independence** | Independent; consumed by Context Engine |

---

## 2.11 Business Tools Gateway (Digital Hands)

| Field | Description |
|-------|-------------|
| **Goal** | Execute business actions via existing domain without bypassing it |
| **Responsibility** | Tool catalog, schema, dry-run, execute, partial-failure compensation |
| **Example tools** | Customer lookup; invoice/rental/tailoring status; delivery dates; dress/inventory availability; create booking/invoice (if allowed); reschedule; balance/deposit inquiry; internal follow-up ticket |
| **Inputs** | ToolCall from Orchestrator + Permission Ticket |
| **Outputs** | ToolResult + Audit event |
| **Depends on** | Permission Engine + Tenant Domain Services |
| **Independence** | Channel-agnostic |

---

## 2.12 Permission Engine (Capability Firewall)

| Field | Description |
|-------|-------------|
| **Goal** | Define what the digital employee may do |
| **Responsibility** | Allow/Deny per Capability, value ceilings, Require Approval, Mode overlays |
| **Inputs** | Agent Role Policy + Runtime Mode + Action intent |
| **Outputs** | `allow` \| `deny` \| `require_human_approval` |
| **Depends on** | Owner settings + plan Entitlements |
| **Independence** | Independent Policy Service |

Detail: [permission-engine.md](./permission-engine.md).

---

## 2.13 Human Handover & Collaboration Desk

| Field | Description |
|-------|-------------|
| **Goal** | Transfer conversation ownership between AI and staff |
| **Responsibility** | Escalation rules, human queue, Resume AI, handover notes, quiet hours |
| **Inputs** | Escalation signal + Conversation |
| **Outputs** | Ownership change + Staff notification |
| **Depends on** | Conversation Manager + Notifications |
| **Independence** | Logically independent |

Detail: [human-handover.md](./human-handover.md).

---

## 2.14 Reply Generator & Tone Policy

| Field | Description |
|-------|-------------|
| **Goal** | Final reply in atelier language/persona |
| **Responsibility** | Template + LLM compose, prevent internal leakage, channel constraints (WhatsApp length vs Email) |
| **Inputs** | Decision + Facts + Persona |
| **Outputs** | Outbound message parts |
| **Depends on** | Orchestrator + Channel constraints |

---

## 2.15 Automation Engine

| Field | Description |
|-------|-------------|
| **Goal** | Non-live scenarios (reminders, follow-ups, SLA breach) |
| **Responsibility** | Triggers / Conditions / Actions for Agent or staff alert |
| **Inputs** | Domain events + time events |
| **Outputs** | Scheduled messages or internal tasks |
| **Depends on** | Conversation / Tools / Notifications |
| **Independence** | Relatively independent |

---

## 2.16 Notification Service

| Field | Description |
|-------|-------------|
| **Goal** | Staff and operational alerts |
| **Responsibility** | Escalations, pending approvals, channel failures, daily digest |
| **Inputs** | Events from Handover / Tools / Ingress |
| **Outputs** | In-app / Email / (future Push) |
| **Note** | Reuses existing Tenant notification channels |

---

## 2.17 Analytics & Quality

| Field | Description |
|-------|-------------|
| **Goal** | Measure digital employee performance |
| **Responsibility** | Containment rate, handover rate, first-response time, tool success, CSAT proxy, token cost |
| **Inputs** | Telemetry events |
| **Outputs** | Tenant + Platform dashboards (conceptual) |
| **Independence** | Independent read model |

---

## 2.18 AI Training & Persona Studio

| Field | Description |
|-------|-------------|
| **Goal** | Shape employee persona and reply rules without rebuilding the system |
| **Responsibility** | Persona, few-shot examples, tone, languages, Do/Don't, simulation tests |
| **Inputs** | Owner inputs from Wizard |
| **Outputs** | Persona Profile + Eval suites |
| **Depends on** | KB + Orchestrator (runtime) |

---

## 2.19 Audit, Compliance & Safety

| Field | Description |
|-------|-------------|
| **Goal** | Full accountability like a human employee |
| **Responsibility** | Decision log, tool calls, permission denials, audit export, PII redaction in logs |
| **Independence** | Cross-cutting across all paths |

---

## 2.20 Entitlement & Billing Gate

| Field | Description |
|-------|-------------|
| **Goal** | Tie platform to subscription plan (`ai_agent` feature) |
| **Responsibility** | Enable/disable, message limits, channel limits |
| **Depends on** | Central Plans / Subscriptions |

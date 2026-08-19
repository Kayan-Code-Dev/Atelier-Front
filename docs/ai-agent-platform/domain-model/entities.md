# Domain Entities

Each entity below is a **conceptual** domain object. Ownership means business authority, not a database FK.

Legend for Aggregate Root: **Yes** / **No** (part of another aggregate).

---

## Identity & Tenancy

### Tenant
| Field | Content |
|-------|---------|
| **Definition** | An atelier workspace on DressnMore SaaS. |
| **Goal** | Absolute isolation boundary for all agent work. |
| **Responsibility** | Owns agents, conversations, knowledge, and tool access for one atelier. |
| **Lifecycle** | Provisioned → Active → Suspended → Deprovisioned |
| **Owned by** | Platform (subscription); operated by atelier owner |
| **Create** | Platform provisioning |
| **Modify** | Platform + atelier owner (profile/settings within rights) |
| **Delete** | Platform only (controlled offboarding) |
| **Aggregate Root** | Yes (external to Agent Platform; referenced everywhere) |
| **Key relations** | Has many AI Agents, Channel Accounts, Conversations, Knowledge Collections |

### Channel
| Field | Content |
|-------|---------|
| **Definition** | A communication medium type (WhatsApp, Messenger, Web Chat, …). |
| **Goal** | Classify how messages enter/leave the platform. |
| **Responsibility** | Channel semantics and constraints (length, media). |
| **Lifecycle** | Catalog entry: Available → Deprecated |
| **Owned by** | Platform |
| **Create / Modify / Delete** | Platform product (catalog) |
| **Aggregate Root** | No (catalog concept; often modeled as enum + adapter identity) |
| **Key relations** | Used by Channel Account; constrains Message Content |

### Channel Account
| Field | Content |
|-------|---------|
| **Definition** | A concrete connected account on a Channel, bound to exactly one Tenant. |
| **Goal** | Tenant resolution for inbound traffic without guessing. |
| **Responsibility** | Hold provider identifiers, binding status, linked Agent. |
| **Lifecycle** | Connecting → Active → Paused → Disconnected |
| **Owned by** | Tenant (atelier) under Platform binding registry |
| **Create** | Atelier admin via Wizard |
| **Modify** | Atelier admin (config); Platform (health flags) |
| **Delete** | Atelier admin disconnect; Platform purge on offboarding |
| **Aggregate Root** | **Yes** |
| **Key relations** | Belongs to Tenant; of type Channel; may bind to AI Agent; originates Conversations |

### Contact
| Field | Content |
|-------|---------|
| **Definition** | External person identity as seen from a channel (phone, page-scoped id, email). |
| **Goal** | Stable handle for the sender before/without customer match. |
| **Responsibility** | Channel-side identity; link candidate to Customer. |
| **Lifecycle** | Observed → Matched → Merged / Anonymous |
| **Owned by** | Tenant |
| **Create** | System on first inbound message |
| **Modify** | System (identity merge); Staff (manual link) |
| **Delete** | Rare; privacy purge by Tenant/Platform policy |
| **Aggregate Root** | No (often under Conversation or Identity service) |
| **Key relations** | May map to Customer Reference; participates in Conversation |

### Customer Reference
| Field | Content |
|-------|---------|
| **Definition** | Pointer from Agent Platform to a Tenant business Customer (not a duplicate CRM record). |
| **Goal** | Ground conversations in atelier customer truth. |
| **Responsibility** | Correlation only; business customer data stays in Tenant Ops domain. |
| **Lifecycle** | Unresolved → Linked → Detached |
| **Owned by** | Tenant |
| **Create / Modify** | Contact Resolver / Staff |
| **Delete** | Unlink only (customer remains in Ops) |
| **Aggregate Root** | No |
| **Key relations** | Linked from Contact/Conversation; feeds Context Bundle |

### Human Staff
| Field | Content |
|-------|---------|
| **Definition** | A tenant user who can own conversations and approve actions. |
| **Goal** | Human coworker of the digital employee. |
| **Responsibility** | Take handover, approve, reply, return ownership to AI. |
| **Lifecycle** | Active staff identity (follows Tenant user lifecycle) |
| **Owned by** | Tenant |
| **Create / Modify / Delete** | Tenant user administration (outside Agent Platform core) |
| **Aggregate Root** | No (reference to Tenant User) |
| **Key relations** | Receives Handover; decides Approvals; may create Tasks |

---

## Agent Core

### AI Agent
| Field | Content |
|-------|---------|
| **Definition** | The digital employee instance configured for a Tenant. |
| **Goal** | Act as an accountable worker with persona, mode, and capabilities. |
| **Responsibility** | Define how the atelier’s AI employee behaves and what it may do. |
| **Lifecycle** | Draft → Testing → Active → Paused → Retired |
| **Owned by** | Tenant |
| **Create** | Atelier admin (Wizard) |
| **Modify** | Atelier admin (persona, mode, capabilities) |
| **Delete** | Soft-retire by atelier admin; hard purge by Platform policy |
| **Aggregate Root** | **Yes** |
| **Key relations** | Has Persona, Capability Policy, Operating Mode; bound to Channel Accounts; participates in Conversations |

### Persona
| Field | Content |
|-------|---------|
| **Definition** | Voice, name, language, tone, and speaking rules of the AI Agent. |
| **Goal** | Make the employee sound like the atelier brand. |
| **Responsibility** | Constrain reply style and Do/Don’t language. |
| **Lifecycle** | Draft → Published → Superseded |
| **Owned by** | AI Agent (Tenant) |
| **Create / Modify** | Atelier admin / Training Studio |
| **Delete** | Supersede; retain history for audit |
| **Aggregate Root** | No (inside AI Agent aggregate) |
| **Key relations** | Uses Prompt Templates; shapes Reply generation |

### Prompt Template
| Field | Content |
|-------|---------|
| **Definition** | Reusable instruction pattern for a situation (greeting, clarification, escalation notice). |
| **Goal** | Stabilize outputs without hardcoding channel logic. |
| **Responsibility** | Provide structured prompt fragments for Orchestrator/Reply. |
| **Lifecycle** | Draft → Active → Deprecated |
| **Owned by** | Tenant (or Platform library copied into Tenant) |
| **Create / Modify** | Atelier admin / Platform librarians |
| **Delete** | Deprecate preferred over hard delete |
| **Aggregate Root** | No |
| **Key relations** | Referenced by Persona; may bind to Operating Mode |

### Capability
| Field | Content |
|-------|---------|
| **Definition** | A named ability the agent may be granted (e.g. read order status, create booking). |
| **Goal** | Atomic unit of permission vocabulary. |
| **Responsibility** | Catalog what “allowed to do X” means. |
| **Lifecycle** | Platform catalog: Available → Deprecated |
| **Owned by** | Platform (definition); Tenant assigns |
| **Create / Modify / Delete** | Platform product for definitions |
| **Aggregate Root** | No (catalog) |
| **Key relations** | Included in Capability Policy; gates Tools |

### Capability Policy
| Field | Content |
|-------|---------|
| **Definition** | The set of Capabilities, ceilings, and approval rules for one AI Agent. |
| **Goal** | Enforce least privilege for the digital employee. |
| **Responsibility** | Answer allow / deny / require approval for intents and tools. |
| **Lifecycle** | Draft → Active → Revised |
| **Owned by** | AI Agent (Tenant) |
| **Create / Modify** | Atelier admin |
| **Delete** | Replace with new revision; retain prior for audit |
| **Aggregate Root** | No (inside AI Agent aggregate) |
| **Key relations** | Contains Capabilities; consulted before Tool Execution |

### Agent Health Snapshot
| Field | Content |
|-------|---------|
| **Definition** | Point-in-time operational health of an AI Agent (channel up, model reachable, error rate). |
| **Goal** | Operate the employee like staffing health, not a chatbot uptime badge alone. |
| **Responsibility** | Surface degraded/paused conditions to admins. |
| **Lifecycle** | Ephemeral observations |
| **Owned by** | Platform observability / Tenant view |
| **Create** | System |
| **Modify / Delete** | System (rolling) |
| **Aggregate Root** | No |
| **Key relations** | About AI Agent; may trigger Notifications |

---

## Conversation

### Conversation
| Field | Content |
|-------|---------|
| **Definition** | An operational work unit between a Contact and the atelier (AI and/or Human), on one primary Channel Account. |
| **Goal** | Track ownership, state, and outcomes of customer interaction. |
| **Responsibility** | Hold messages, state, owner, memory, and handover linkage. |
| **Lifecycle** | New → Active (AI/Human) → Resolved → Closed (also Snoozed / Blocked) |
| **Owned by** | Tenant |
| **Create** | System on first accepted inbound (or staff outbound start) |
| **Modify** | System (state/owner); Staff (notes, priority); never change Tenant |
| **Delete** | Soft-close/archive; purge only under retention policy |
| **Aggregate Root** | **Yes** |
| **Key relations** | Has Messages, Memory, optional Summary; may have Handover; uses Context Bundle; may spawn Tasks/Approvals |

### Conversation Message
| Field | Content |
|-------|---------|
| **Definition** | One utterance or system notice inside a Conversation. |
| **Goal** | Immutable record of what was said or system-noted. |
| **Responsibility** | Direction, type, content, author kind (customer/AI/human/system). |
| **Lifecycle** | Received/Created → Delivered/Failed (outbound) → Immutable |
| **Owned by** | Conversation |
| **Create** | Channel ingress, AI Reply, Staff reply, System |
| **Modify** | Generally immutable; status fields only for delivery |
| **Delete** | Redaction/purge under policy; not casual delete |
| **Aggregate Root** | No |
| **Key relations** | Belongs to one Conversation; may reference Tool Execution or Approval |

### Conversation Memory
| Field | Content |
|-------|---------|
| **Definition** | Working memory for a Conversation (short window, extracted facts, open tasks). |
| **Goal** | Support Context Engine without replaying entire history every time. |
| **Responsibility** | Maintain rolling summary and structured memory slices. |
| **Lifecycle** | Updated continuously → Frozen on Close |
| **Owned by** | Conversation |
| **Create / Modify** | System (Memory updater) |
| **Delete** | With conversation purge |
| **Aggregate Root** | No |
| **Key relations** | Feeds Context Bundle; derived from Messages + Tool Results |

### Conversation Summary
| Field | Content |
|-------|---------|
| **Definition** | Compressed narrative of a Conversation for handover, analytics, or archive. |
| **Goal** | Human-readable and machine-usable condensation. |
| **Responsibility** | Capture outcome, intents, pending items. |
| **Lifecycle** | Draft → Published (on resolve/handover) → Superseded if reopened |
| **Owned by** | Conversation |
| **Create / Modify** | System; Staff may annotate |
| **Delete** | Retention policy |
| **Aggregate Root** | No |
| **Key relations** | Produced from Conversation; attached to Handover packet |

### Context Bundle
| Field | Content |
|-------|---------|
| **Definition** | Single curated pack of policy, persona, memory, customer, facts, knowledge, and channel limits for one decision cycle. |
| **Goal** | Ensure the agent reasons on permission-aware, selective context. |
| **Responsibility** | Versioned snapshot consumed by Orchestrator. |
| **Lifecycle** | Assembled → Consumed → Archived (for audit) |
| **Owned by** | Conversation decision cycle (Tenant-scoped) |
| **Create** | Context Engine (system) |
| **Modify** | Immutable after assemble |
| **Delete** | Retention policy |
| **Aggregate Root** | No (snapshot; may be treated as VO-rich entity) |
| **Key relations** | Built from Memory, Knowledge, Customer Ref, Settings, Capability Policy |

### Context Provider Contribution
| Field | Content |
|-------|---------|
| **Definition** | One source’s contribution slice into a Context Bundle (memory, KB, customer facts, …). |
| **Goal** | Trace which provider supplied which facts. |
| **Responsibility** | Citation and freshness metadata. |
| **Lifecycle** | Ephemeral within bundle assembly |
| **Owned by** | Context Bundle assembly |
| **Create** | System providers |
| **Modify / Delete** | N/A (immutable slice) |
| **Aggregate Root** | No |
| **Key relations** | Part of Context Bundle |

### Human Handover
| Field | Content |
|-------|---------|
| **Definition** | Explicit transfer of Conversation ownership between AI and Human Staff. |
| **Goal** | Safe collaboration on complex or sensitive work. |
| **Responsibility** | Reason, packet, assignee, resume rules. |
| **Lifecycle** | Requested → Active → Finished (returned to AI or closed) |
| **Owned by** | Conversation |
| **Create** | System (policy) or Staff (force take) or Customer request detection |
| **Modify** | Assignee, notes |
| **Delete** | Not deleted; closed with history |
| **Aggregate Root** | No (inside Conversation aggregate; critical entity) |
| **Key relations** | Involves Human Staff; carries Conversation Summary; emits Notifications |

### Approval Request
| Field | Content |
|-------|---------|
| **Definition** | Request for a human to allow a sensitive Tool or outbound action. |
| **Goal** | Enforce require_approval without silent execution. |
| **Responsibility** | Hold proposed action, ceilings, and decision. |
| **Lifecycle** | Requested → Granted / Rejected / TimedOut |
| **Owned by** | Tenant (linked to Conversation / Tool Execution) |
| **Create** | Permission Engine / Orchestrator |
| **Modify** | Decision by Human Staff |
| **Delete** | Retain for audit |
| **Aggregate Root** | **Yes** (decision lifecycle) |
| **Key relations** | About Tool Execution or Reply; decided by Human Staff |

### Approval Decision
| Field | Content |
|-------|---------|
| **Definition** | The outcome record of an Approval Request. |
| **Goal** | Immutable accountability for allow/deny by a human. |
| **Responsibility** | Who decided, when, and why. |
| **Lifecycle** | Created once; immutable |
| **Owned by** | Approval Request |
| **Create** | Human Staff / timeout system |
| **Modify / Delete** | None |
| **Aggregate Root** | No |
| **Key relations** | Belongs to Approval Request |

---

## Knowledge & Training

### Knowledge Collection
| Field | Content |
|-------|---------|
| **Definition** | Named set of knowledge documents for an Agent/Tenant (policies, FAQ, services). |
| **Goal** | Organize atelier brain content. |
| **Responsibility** | Versioning and publish scope. |
| **Lifecycle** | Draft → Published → Archived |
| **Owned by** | Tenant |
| **Create / Modify** | Atelier admin |
| **Delete** | Archive preferred |
| **Aggregate Root** | **Yes** |
| **Key relations** | Contains Knowledge Documents; feeds Context |

### Knowledge Document
| Field | Content |
|-------|---------|
| **Definition** | A unit of unstructured or semi-structured knowledge. |
| **Goal** | Answer policy/FAQ style questions. |
| **Responsibility** | Content, status, citations. |
| **Lifecycle** | Draft → Published → Superseded |
| **Owned by** | Knowledge Collection |
| **Create / Modify** | Atelier admin / import |
| **Delete** | Supersede/archive |
| **Aggregate Root** | No |
| **Key relations** | Belongs to Collection; cited in Context Bundle |

### Knowledge Source
| Field | Content |
|-------|---------|
| **Definition** | Origin descriptor (manual upload, wizard seed, synced settings, external provider). |
| **Goal** | Extensibility and trust labeling. |
| **Responsibility** | Provenance of documents. |
| **Lifecycle** | Registered → Active → Disabled |
| **Owned by** | Tenant or Platform |
| **Create** | Admin / Platform |
| **Aggregate Root** | No |
| **Key relations** | Supplies Documents |

### Learning Record
| Field | Content |
|-------|---------|
| **Definition** | Captured example of good/bad agent behavior for improvement. |
| **Goal** | Feed training without silent self-modification in production. |
| **Responsibility** | Label outcome, link conversation excerpt. |
| **Lifecycle** | Captured → Reviewed → Accepted / Rejected |
| **Owned by** | Tenant |
| **Create** | Staff / QA |
| **Modify** | Reviewer |
| **Delete** | Retention |
| **Aggregate Root** | No |
| **Key relations** | May join Training Dataset |

### Training Dataset
| Field | Content |
|-------|---------|
| **Definition** | Curated set of Learning Records / examples for persona or eval suites. |
| **Goal** | Controlled improvement and regression tests. |
| **Responsibility** | Versioned training material. |
| **Lifecycle** | Open → Locked → Applied |
| **Owned by** | Tenant (or Platform shared templates) |
| **Create / Modify** | Atelier admin / Platform |
| **Aggregate Root** | **Yes** (optional training aggregate) |
| **Key relations** | Contains Learning Records; informs Persona revisions |

---

## Business Tools

### Tool
| Field | Content |
|-------|---------|
| **Definition** | Named business action or query exposed to the Agent (e.g. GetOrderStatus). |
| **Goal** | Digital hands into Tenant Ops — never channel-aware. |
| **Responsibility** | Declare intent, input/output meaning, side-effect class (read/write). |
| **Lifecycle** | Registered → Enabled → Disabled → Deprecated |
| **Owned by** | Platform catalog; enabled per Tenant/Agent policy |
| **Create** | Platform / domain owners |
| **Modify** | Platform (definition); Tenant enables |
| **Delete** | Deprecate |
| **Aggregate Root** | No (catalog); executions are separate |
| **Key relations** | Guarded by Capability; produces Tool Executions |

### Tool Execution
| Field | Content |
|-------|---------|
| **Definition** | One attempt to run a Tool for a Conversation decision. |
| **Goal** | Traceable act with permission ticket. |
| **Responsibility** | Inputs, status, timing, permission outcome. |
| **Lifecycle** | Authorized → Running → Succeeded / Failed / Denied |
| **Owned by** | Tenant (Conversation-scoped) |
| **Create** | Orchestrator via Tool Gateway |
| **Modify** | System status only |
| **Delete** | Never (audit) |
| **Aggregate Root** | **Yes** |
| **Key relations** | Yields Tool Result; may require Approval; writes Audit |

### Tool Result
| Field | Content |
|-------|---------|
| **Definition** | Outcome payload or error of a Tool Execution. |
| **Goal** | Facts for Context/Reply or failure reason for Handover. |
| **Responsibility** | Structured success/failure semantics. |
| **Lifecycle** | Created once with Execution terminal state |
| **Owned by** | Tool Execution |
| **Create** | Tool Gateway |
| **Modify / Delete** | Immutable |
| **Aggregate Root** | No |
| **Key relations** | Belongs to Tool Execution; may update Memory |

### Business Object Reference
| Field | Content |
|-------|---------|
| **Definition** | Reference to Tenant Ops entities (Reservation/Booking signal, Invoice, Order, Product/Dress) without owning them. |
| **Goal** | Talk about atelier objects safely across contexts. |
| **Responsibility** | Type + identity pointer only. |
| **Lifecycle** | Created when tools/context cite objects |
| **Owned by** | Referencing Conversation/Tool Result |
| **Aggregate Root** | No |
| **Key relations** | Cited in Context Bundle and Tool Results |

> Note: **Reservation, Invoice, Order, Product** are **Anti-Corruption references** to Tenant Ops. They are not mastered inside AI Agent Platform.

---

## Automation & Tasks

### Automation Workflow
| Field | Content |
|-------|---------|
| **Definition** | Named automated scenario (reminder, SLA breach follow-up). |
| **Goal** | Act without a live customer message when triggers fire. |
| **Responsibility** | Trigger + steps + guardrails. |
| **Lifecycle** | Draft → Active → Paused → Retired |
| **Owned by** | Tenant |
| **Create / Modify** | Atelier admin |
| **Delete** | Retire |
| **Aggregate Root** | **Yes** |
| **Key relations** | Has Workflow Steps; creates Tasks; may start Conversations or Notifications |

### Workflow Step
| Field | Content |
|-------|---------|
| **Definition** | One step in an Automation Workflow. |
| **Goal** | Ordered action unit (wait, notify, message, create task). |
| **Responsibility** | Step type and parameters. |
| **Lifecycle** | Follows parent workflow revisions |
| **Owned by** | Automation Workflow |
| **Aggregate Root** | No |

### Task
| Field | Content |
|-------|---------|
| **Definition** | Work item for Human Staff or Agent follow-up. |
| **Goal** | Track unfinished operational work outside pure chat. |
| **Responsibility** | Assignee, due, status, linked Conversation. |
| **Lifecycle** | Open → InProgress → Done / Cancelled |
| **Owned by** | Tenant |
| **Create** | Handover, Automation, Staff |
| **Modify** | Assignee / Staff |
| **Delete** | Cancel preferred |
| **Aggregate Root** | No (or small Task aggregate if standalone) |
| **Key relations** | May link Conversation, Approval, Workflow |

---

## Observability

### Notification
| Field | Content |
|-------|---------|
| **Definition** | Alert to Human Staff or platform operators about agent events. |
| **Goal** | Make handover/approvals/failures actionable. |
| **Responsibility** | Type, recipients, payload summary. |
| **Lifecycle** | Created → Delivered → Read / Dismissed |
| **Owned by** | Tenant (staff) or Platform |
| **Create** | System |
| **Modify** | Read state by Staff |
| **Delete** | Retention |
| **Aggregate Root** | No |
| **Key relations** | Triggered by Handover, Approval, Tool failure, Channel health |

### Audit Record
| Field | Content |
|-------|---------|
| **Definition** | Immutable accountability entry for decisions, tools, denials, ownership changes. |
| **Goal** | Employee-grade audit trail. |
| **Responsibility** | Who/what/when/why within Tenant scope. |
| **Lifecycle** | Append-only |
| **Owned by** | Tenant (visible); Platform (compliance access) |
| **Create** | System only |
| **Modify / Delete** | Forbidden (except legal redact procedures) |
| **Aggregate Root** | No (append-only log stream) |
| **Key relations** | References Conversation, Tool Execution, Approval, Agent |

### Analytics Event
| Field | Content |
|-------|---------|
| **Definition** | Measurement fact (containment, first response time, tool success, token cost proxy). |
| **Goal** | Quality and cost insight without changing behavior. |
| **Responsibility** | Emit metrics-friendly facts. |
| **Lifecycle** | Emitted → Aggregated |
| **Owned by** | Tenant / Platform analytics |
| **Create** | System |
| **Modify / Delete** | Pipeline only |
| **Aggregate Root** | No |
| **Key relations** | Sourced from Conversation and Tool outcomes |

---

## Entity inventory (quick list)

AI Agent · Persona · Prompt Template · Capability · Capability Policy · Agent Health Snapshot · Tenant · Channel · Channel Account · Contact · Customer Reference · Human Staff · Conversation · Conversation Message · Conversation Memory · Conversation Summary · Context Bundle · Context Provider Contribution · Human Handover · Approval Request · Approval Decision · Knowledge Collection · Knowledge Document · Knowledge Source · Learning Record · Training Dataset · Tool · Tool Execution · Tool Result · Business Object Reference · Automation Workflow · Workflow Step · Task · Notification · Audit Record · Analytics Event

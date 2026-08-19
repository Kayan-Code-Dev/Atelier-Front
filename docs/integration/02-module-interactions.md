# 02 — Module Interactions

For each AOS module: responsibilities, I/O, dependencies, events, extension points.

Legend: **Pub** = publishes · **Sub** = consumes (conceptually / future subscribers)

---

## Foundation — `aos.core` / `aos.events` / `aos.observability`

| Field | Content |
|-------|---------|
| **Responsibilities** | Kernel boot, module registry, configuration, event bus port, logging/health/metrics ports |
| **Inputs** | App bootstrap, feature flags, module registrations |
| **Outputs** | Ready kernel, discoverable modules, observability handles |
| **Dependencies** | None (foundation) |
| **Consumed Events** | — |
| **Published Events** | Kernel/module lifecycle (conceptual) |
| **Extension Points** | New modules via Module Registry; observability adapters |

---

## Communication Hub — `aos.communication` (Sprint 11)

| Field | Content |
|-------|---------|
| **Responsibilities** | Channel registry, webhook validation, normalize inbound, route conversation id, outbound send, delivery/read/typing, comment classification |
| **Inputs** | Provider webhook payloads, outbound NormalizedMessage |
| **Outputs** | NormalizedMessage, delivery records, routed conversation key |
| **Dependencies** | `aos.core`, `aos.events` (no Planner/Memory/Tools) |
| **Consumed Events** | Delivery updates (future from adapters) |
| **Published Events** | ChannelRegistered/Connected/Disconnected, MessageReceived/Normalized/Sent/Delivered/Read/Failed, AttachmentUploaded, ConversationRouted, CommentClassified, PrivateConversationStarted |
| **Extension Points** | `ChannelAdapterInterface`, channel policies, routers |

---

## Conversation Engine — `aos.conversation` (Sprint 2)

| Field | Content |
|-------|---------|
| **Responsibilities** | Conversation aggregate, ownership (AI/Human), state transitions, message log pointers |
| **Inputs** | TenantBinding + NormalizedMessage |
| **Outputs** | Conversation session, ownership, state |
| **Dependencies** | Foundation; Identity/Context for binding |
| **Consumed Events** | MessageNormalized, HandoverRequested, ToolSucceeded (conceptual) |
| **Published Events** | ConversationStarted/Updated/Closed, OwnershipChanged, StateTransitioned |
| **Extension Points** | State hooks, ownership policies |

---

## Identity & Context Engine — `aos-context` / related (Sprint 3)

| Field | Content |
|-------|---------|
| **Responsibilities** | Tenant resolution, contact/customer twin, isolation guard, context envelope |
| **Inputs** | Channel account / external ids |
| **Outputs** | TenantBinding, CustomerRef/UnknownContact, isolation token |
| **Dependencies** | Channel binding registry (conceptual); Conversation |
| **Consumed Events** | ChannelConnected, MessageReceived |
| **Published Events** | TenantResolved, IdentityMatched, IsolationViolationRejected |
| **Extension Points** | Identity matchers, binding stores |

---

## Business Tool Gateway — `aos.tools` (Sprint 4)

| Field | Content |
|-------|---------|
| **Responsibilities** | Tool registry, validation, execution port, result normalization, audit/analytics hooks |
| **Inputs** | Tool call intents from Planner/Orchestration |
| **Outputs** | ToolResult (success/fail/timeout) |
| **Dependencies** | Permissions (must authorize first); Observability |
| **Consumed Events** | ApprovalCompleted (resume gated tools) |
| **Published Events** | ToolDiscovered, ToolExecuted, ToolFailed, ToolRejected |
| **Extension Points** | Tool adapters for DressnMore domains |

---

## Permission & Policy Engine — `aos.permissions` (Sprint 5)

| Field | Content |
|-------|---------|
| **Responsibilities** | Capability firewall, operating mode constraints, approval requirements, risk gates |
| **Inputs** | Actor/employee context, capability key, mode, tool metadata |
| **Outputs** | Allow / Deny / RequireApproval |
| **Dependencies** | Foundation only (policy definitions) |
| **Consumed Events** | ModeChanged, PolicyUpdated |
| **Published Events** | PermissionGranted, PermissionDenied, ApprovalRequired |
| **Extension Points** | Policy packs, mode matrices |

---

## AI Planner — `aos.planner` (Sprint 6)

| Field | Content |
|-------|---------|
| **Responsibilities** | Intent, goals, immutable execution plan, planned tools, risk, decision path |
| **Inputs** | Conversation snapshot, context hints, policies |
| **Outputs** | ExecutionPlan |
| **Dependencies** | Permissions (conceptual); does not call Providers/Tools directly in pure form |
| **Consumed Events** | MessageNormalized, MemoryRetrieved, KnowledgeRetrieved |
| **Published Events** | PlanCreated, PlanRevised, PlanRejected, ApprovalGateInserted |
| **Extension Points** | Planning strategies, risk scorers |

---

## Prompt Engine — `aos.prompts` (Sprint 7)

| Field | Content |
|-------|---------|
| **Responsibilities** | Persona, sections, inject context/plan/rules, guard, optimize, version |
| **Inputs** | Planning result, persona, memory/knowledge packs, safety rules |
| **Outputs** | Prompt Ready (+ metadata/version) |
| **Dependencies** | Consumes Memory/Knowledge **outputs** only (no engine ownership) |
| **Consumed Events** | PlanCreated, MemoryContextReady, KnowledgeContextReady |
| **Published Events** | PromptGenerationStarted, PromptBuilt/Validated/Optimized/Rejected, PromptGuardTriggered, PromptVersionCreated |
| **Extension Points** | Personas, templates, guards, optimizers |

---

## Memory Engine — `aos.memory` (Sprint 8)

| Field | Content |
|-------|---------|
| **Responsibilities** | Classify/store facts, retrieve/rank, summaries, snapshots, expiration |
| **Inputs** | Conversation updates; retrieval requests |
| **Outputs** | MemoryContext; MemoryRecords |
| **Dependencies** | Store/index ports only; no Provider/Tools |
| **Consumed Events** | ConversationUpdated, MessageReceived (for write pipeline) |
| **Published Events** | MemoryCreated/Updated/Expired/Retrieved/Ranked/Summarized/Merged/Discarded, SnapshotGenerated |
| **Extension Points** | MemoryStore, MemoryIndex, rankers |

---

## Knowledge Engine — `aos.knowledge` (Sprint 9)

| Field | Content |
|-------|---------|
| **Responsibilities** | Sources, collections, documents, publish lifecycle, search, rank, context |
| **Inputs** | KnowledgeRetrievalRequest; publish commands |
| **Outputs** | KnowledgeContext; documents/packs |
| **Dependencies** | Search engine port; no embeddings/vectors required in current sprint |
| **Consumed Events** | Knowledge publish commands |
| **Published Events** | KnowledgeCreated/Updated/Published/Archived/Retrieved/Ranked/SearchCompleted/Rejected/VersionCreated/PolicyApplied |
| **Extension Points** | Search engines, source adapters, visibility policies |

---

## AI Provider Platform — `aos.ai` (Sprint 10)

| Field | Content |
|-------|---------|
| **Responsibilities** | Provider/model registry, selection, completion/streaming, retry/fallback, cost/latency policies |
| **Inputs** | AiRequest (prompt, capabilities, budget, stream flag) |
| **Outputs** | AiResponse / stream chunks |
| **Dependencies** | `AiProviderInterface` plugins only |
| **Consumed Events** | — (invoked synchronously by orchestration) |
| **Published Events** | ProviderSelected/Failed/Recovered, CompletionRequested/Received, StreamingStarted/Completed, FallbackActivated, BudgetExceeded, ModelChanged |
| **Extension Points** | Real provider plugins (HTTP/SDK in future adapters package) |

---

## Workflow & Automation — `aos.workflow` (Sprint 12)

| Field | Content |
|-------|---------|
| **Responsibilities** | Definitions, triggers, conditions, task dispatch, retries/DLQ model, monitoring |
| **Inputs** | Trigger payloads |
| **Outputs** | WorkflowExecutionResult |
| **Dependencies** | TaskDispatcher port; may *orchestrate* tools/notifications via adapters later |
| **Consumed Events** | Domain triggers (message, comment, invoice, …) |
| **Published Events** | WorkflowStarted/Completed/Paused/Cancelled/Archived, TaskStarted/Completed/Failed, ApprovalRequested/Completed, RetryTriggered |
| **Extension Points** | Task types, triggers, repositories, schedulers |

---

## AI Workspace — `apps/ai-workspace` (Sprint 13)

| Field | Content |
|-------|---------|
| **Responsibilities** | Operator UX for inbox, conversation, planner monitor, memory/knowledge explorers, workflows, employees, analytics, channels, approvals |
| **Inputs** | Mock data today; future read models / APIs |
| **Outputs** | Operator actions (approve, reconnect, publish) — future command ports |
| **Dependencies** | None on PHP packages at runtime today |
| **Consumed Events** | Display projections of domain events (future) |
| **Published Events** | Operator command intents (future) |
| **Extension Points** | Feature modules, design system, query adapters |

---

## Interaction summary (who talks to whom)

| From | To | Via |
|------|----|-----|
| Communication | Conversation | NormalizedMessage + route key |
| Conversation | Planner | Conversation snapshot |
| Planner | Memory / Knowledge | Retrieval requests |
| Memory / Knowledge | Prompt | Context packs |
| Prompt | AI Provider | Prompt + model requirements |
| Planner / Orchestration | Permissions | Capability check |
| Permissions → allow | Tools | Tool call |
| Tools | DressnMore adapters | Tool contracts |
| Tools / Provider | Reply Pipeline | Results |
| Reply Pipeline | Communication | Outbound send |
| Triggers | Workflow | TriggerType payload |
| Workspace | All modules | Future ports / projections |

# Aggregates

Aggregates define **consistency boundaries**. Invariants inside an aggregate are enforced together; outside references are by identity only.

---

## 1. AIAgent Aggregate

**Root:** `AIAgent`

**Inside boundary:**
- Persona (current published + draft)
- Capability Policy (active revision)
- Applied Operating Mode setting
- Bound Channel Account ids (references)
- Prompt Template bindings used by this agent

**Invariants guarded here:**
- Agent belongs to exactly one Tenant
- Active Capability Policy is a single revision
- Retired agents cannot be bound to new Channel Accounts

**Outside:** Conversations reference `agentId` but are not inside this aggregate.

---

## 2. ChannelAccount Aggregate

**Root:** `ChannelAccount`

**Inside boundary:**
- Provider identifiers
- Binding status
- Linked `tenantId` (immutable after activate)
- Optional linked `agentId`

**Invariants:**
- One Channel Account → exactly one Tenant
- Cannot activate without verified provider identity
- Disconnected accounts cannot create new Conversations

---

## 3. Conversation Aggregate

**Root:** `Conversation`

**Inside boundary:**
- Conversation Messages
- Conversation Memory
- Conversation Summary (current)
- Human Handover (active + history references)
- Ownership (AI | Human | SharedAssist)
- Conversation State
- Pointers to latest Context Bundle id / Tool Execution ids (by reference)

**Invariants:**
- Exactly one Tenant; never reassigned
- Exactly one owner kind at any moment
- Every Message belongs to this Conversation only
- Active Handover implies Human ownership (or SharedAssist per policy)
- Closed conversations reject new customer-driven AI tool writes

**Outside:** Approval Requests and Tool Executions are separate aggregates referenced by id (to keep tool latency/audit independent), but Conversation state transitions may require their outcomes.

---

## 4. KnowledgeCollection Aggregate

**Root:** `KnowledgeCollection`

**Inside boundary:**
- Knowledge Documents
- Publish status of the collection
- Source registrations for its documents

**Invariants:**
- Published documents only are eligible for Context retrieval
- Document supersession keeps provenance

---

## 5. ToolExecution Aggregate

**Root:** `ToolExecution`

**Inside boundary:**
- Authorization snapshot (permission ticket)
- Execution status
- Tool Result (when terminal)
- Linked `conversationId`, `toolId`, `tenantId`

**Invariants:**
- No execution without permission ticket
- Result immutable once terminal
- Denied executions still persist for audit

---

## 6. ApprovalRequest Aggregate

**Root:** `ApprovalRequest`

**Inside boundary:**
- Proposed action
- Approval Decision (0..1)
- Expiry / timeout policy snapshot

**Invariants:**
- One terminal decision only
- Granted decision required before executing the gated tool/action
- Timeout produces Rejected-or-TimedOut decision, never silent grant

---

## 7. AutomationWorkflow Aggregate

**Root:** `AutomationWorkflow`

**Inside boundary:**
- Workflow Steps (ordered)
- Trigger configuration
- Active/Paused flag

**Invariants:**
- Inactive workflows emit no new runs
- Steps belong to one workflow revision line

**Note:** Concrete **Task** instances created by runs may live as separate small aggregates or as entities linked to workflow run ids — conceptually they are not rewritten inside the workflow definition aggregate.

---

## 8. TrainingDataset Aggregate (optional / Phase 2+)

**Root:** `TrainingDataset`

**Inside boundary:** Learning Records included in a locked version.

**Invariant:** Locked datasets are immutable; improvements create a new version.

---

## Aggregate interaction rules

1. Conversations never embed full Customer masters — only `CustomerRef`.  
2. Tools never embed Channel payloads — only conversation/tool ids.  
3. Cross-aggregate updates happen via **Domain Events**, not direct graph mutation.  
4. Tenant id on every root is mandatory and immutable.

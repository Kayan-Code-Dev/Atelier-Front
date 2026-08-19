# 06 — Context Flow

How context accumulates from **Incoming Message** to **AI Reply**.

## End-to-end path

```
Incoming Message
  → Webhook Validation
  → Channel Resolution
  → Tenant Resolution                    ← TENANT
  → Message Normalization
  → Conversation Load/Create
  → Identity / Customer Twin
  → Policy & Operating Mode snapshot     ← POLICIES
  → Planner Request
       ├─ Memory Retrieval               ← MEMORY
       ├─ Knowledge Retrieval            ← KNOWLEDGE
       └─ Permission envelope            ← PERMISSIONS
  → Execution Plan                       ← PLANNER
  → Prompt Assembly
       (Persona + Plan + Memory + Knowledge + Safety + Localization)
  → AI Provider Completion
  → Tool Authorization                   ← PERMISSIONS again
  → Tool Execution (optional)
  → Reply Composition
  → Outbound Send
  → Post-turn Memory Write (classified)
  → Audit / Analytics
```

## Where each concern is added

| Concern | Insertion point | What is added | What must not happen |
|---------|-----------------|---------------|----------------------|
| **Tenant** | Immediately after channel resolve | `tenant_id`, isolation token, channel binding | Downstream without tenant |
| **Policies** | After conversation load / before plan | Mode, safety, retention, channel limits | Adapters evaluating business policy |
| **Permissions** | Before tools & sensitive plan steps | Allow/Deny/Approve | Tools self-authorizing |
| **Planner** | After conversation+tenant ready | Intent, goals, tool plan, risk, approvals | Planner calling HTTP LLMs directly in core |
| **Memory** | Parallel retrieval before Prompt; write after turn | Ranked facts, summaries | Raw chat transcript as long-term memory |
| **Knowledge** | Parallel retrieval before Prompt | Published docs/packs with confidence | Draft/unpublished leakage across tenants |
| **Prompt** | After plan+contexts | Ordered sections, guard, version | Provider-specific prompt dialects in Domain |
| **Provider** | After Prompt Ready | Completion/usage/cost | Knowing WhatsApp or invoices |
| **Reply** | After completion/tools | Channel-safe outbound text/media | Business writes outside Tools |

## Context pack composition (conceptual)

```
AgentContextBundle
  tenant
  conversation
  customer
  operatingMode
  permissionsSnapshot
  memoryContext
  knowledgeContext
  planningResult
  safetyInstructions
  localization
```

Prompt Engine consumes this bundle; AI Provider receives the **rendered prompt**, not the whole platform graph.

## Isolation rules inside context flow

1. Memory retrieval is scoped by tenant (+ customer when applicable).  
2. Knowledge retrieval applies visibility + tenant isolation policies.  
3. Tool inputs must not include out-of-permission fields (Context Engine redaction).  
4. Cross-tenant context merge is forbidden.

## Reply uses context how?

- Customer language / persona tone → Prompt  
- Facts already known → Memory (avoid re-asking)  
- Policy answers → Knowledge  
- Next actions → Planner tool list  
- Hard stops → Permissions / Approvals

# 01 — System Map

**Document type:** Enterprise system topology  
**Audience:** Architects, integration leads, platform owners

## 1. Canonical vertical slice

```
AI Workspace
    ↓
Communication Hub
    ↓
Conversation Engine
    ↓
Planner
    ↓
Memory
    ↓
Knowledge
    ↓
Prompt Engine
    ↓
AI Provider
    ↓
Business Tool Gateway
    ↓
DressnMore Business Domain
    ↓
Reply Pipeline
```

This is the **authoritative happy-path stack**. Lateral concerns (Permissions, Workflow, Observability, Events, Identity/Context) attach at defined seams without changing the order above.

## 2. Layered system map

```
┌─────────────────────────────────────────────────────────────────┐
│                         AI WORKSPACE                             │
│  Operator surface: Inbox · Conversation · Approvals · Analytics │
│  Sprint 13 · Mock-first today · Observes AOS domain conceptually│
└───────────────────────────────┬─────────────────────────────────┘
                                │ commands / views (future adapters)
┌───────────────────────────────▼─────────────────────────────────┐
│                    COMMUNICATION HUB (Sprint 11)                 │
│  Channel adapters · Webhook gateway · Normalize · Route · Send  │
└───────────────────────────────┬─────────────────────────────────┘
                                │ NormalizedMessage
┌───────────────────────────────▼─────────────────────────────────┐
│                 IDENTITY & CONTEXT + CONVERSATION                │
│  Tenant binding · Contact resolve · Conversation lifecycle      │
│  Sprint 2–3                                                      │
└───────────────────────────────┬─────────────────────────────────┘
                                │ Conversation + TenantContext
┌───────────────────────────────▼─────────────────────────────────┐
│                         AI PLANNER (Sprint 6)                    │
│  Intent · Goals · Execution Plan · Risk · Approval gates         │
└───────────────┬───────────────────────────────┬─────────────────┘
                │                               │
        ┌───────▼───────┐               ┌───────▼───────┐
        │ MEMORY (S8)   │               │ KNOWLEDGE (S9)│
        │ retrieve/rank │               │ search/rank   │
        └───────┬───────┘               └───────┬───────┘
                └───────────────┬───────────────┘
                                │ Context packs
┌───────────────────────────────▼─────────────────────────────────┐
│                      PROMPT ENGINE (Sprint 7)                    │
│  Persona · Sections · Guard · Optimize · Prompt Ready            │
└───────────────────────────────┬─────────────────────────────────┘
                                │ Prompt + requirements
┌───────────────────────────────▼─────────────────────────────────┐
│                   AI PROVIDER PLATFORM (Sprint 10)               │
│  Select · Complete/Stream · Fallback · Cost · Normalize response │
└───────────────────────────────┬─────────────────────────────────┘
                                │ Decision / tool intents
┌───────────────────────────────▼─────────────────────────────────┐
│              PERMISSION & POLICY  →  BUSINESS TOOL GATEWAY       │
│  Sprint 5                              Sprint 4                  │
└───────────────────────────────┬─────────────────────────────────┘
                                │ Tool contracts (only boundary)
┌───────────────────────────────▼─────────────────────────────────┐
│                 DRESSNMORE BUSINESS DOMAIN (future binding)      │
│  Customers · Reservations · Orders · Invoices · Inventory · …    │
└───────────────────────────────┬─────────────────────────────────┘
                                │ Tool results
┌───────────────────────────────▼─────────────────────────────────┐
│                         REPLY PIPELINE                           │
│  Compose reply → Outbound Dispatcher → Delivery tracking         │
│  Parallel: Workflow hooks · Analytics · Audit · Approvals        │
└─────────────────────────────────────────────────────────────────┘
```

## 3. Package ↔ capability map

| Package | Module key | System role |
|---------|------------|-------------|
| `aos-core` | `aos.core` | Kernel, module registry, config |
| `aos-events` | `aos.events` | Event bus contracts |
| `aos-observability` | `aos.observability` | Logging / health / metrics ports |
| `aos-conversation` | `aos.conversation` | Conversation aggregate & lifecycle |
| `aos-context` | (context engine) | Identity/context assembly support |
| `aos-tools` | `aos.tools` | Business Tool Gateway |
| `aos-permissions` | `aos.permissions` | Capability firewall |
| `aos-planner` | `aos.planner` | Execution planning |
| `aos-prompts` | `aos.prompts` | Dynamic prompt build |
| `aos-memory` | `aos.memory` | Memory write/retrieve |
| `aos-knowledge` | `aos.knowledge` | Knowledge retrieve/publish model |
| `aos-ai` | `aos.ai` | LLM provider abstraction |
| `aos-communication` | `aos.communication` | Omni-channel hub |
| `aos-workflow` | `aos.workflow` | Automation engine |
| `apps/ai-workspace` | — | Operator workspace (UI) |

## 4. Cross-cutting rails

| Rail | Attaches where | Must not |
|------|----------------|----------|
| **Events** | After each stage transition | Carry business mutations |
| **Permissions** | Before every tool / sensitive action | Live inside channel adapters |
| **Workflow** | After triggers (message, comment, invoice, …) | Bypass Conversation/Planner for AI turns |
| **Observability** | All stages | Own domain decisions |
| **Approvals / HITL** | Planner risk · Tool risk · Workspace Approvals | Be skipped for Critical tools |

## 5. DressnMore binding seam (explicit)

AOS stops at **Business Tool Gateway contracts**.  
DressnMore Tenant Ops implements adapters **behind** those contracts.

Forbidden: Agent packages importing Laravel models, Eloquent, Controllers, or channel SDKs.

## 6. Reply Pipeline (detail)

```
Tool Result / Model Completion
  → Policy / Safety check
  → Optional Approval gate
  → Outbound message normalize
  → Channel Adapter send
  → Delivery status track
  → Conversation update
  → Memory write (classified facts only)
  → Analytics markers
  → Audit trail
```

## 7. System map invariants

1. Every inbound payload becomes a **Normalized Message** before core processing.  
2. Every outbound reply leaves through **Outbound Dispatcher / Channel Adapter**.  
3. Every Tenant Ops mutation goes through a **Business Tool**.  
4. Planner may request Memory/Knowledge; those engines never call Providers or Tools.  
5. AI Workspace is an **operator surface**, not a channel or a business domain.

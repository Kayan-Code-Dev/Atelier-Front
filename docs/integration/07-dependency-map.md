# 07 — Dependency Map

## Allowed dependency direction (high level)

```
AI Workspace ──(future ports)──► read models / command ports
        │
        ▼
Communication ──► Conversation ──► Planner
                      │               │
                      │               ├──► Memory
                      │               ├──► Knowledge
                      │               └──► Permissions ──► Tools ──► DressnMore Adapters
                      │
                      └──► Prompt ──► AI Provider

Workflow ──► (triggers from events) ──► Task adapters (may call Tools/Notify)

Foundation (core/events/observability) ◄── all modules may depend
```

## Allowed dependencies

| Module | May depend on |
|--------|----------------|
| Communication | core, events, observability |
| Conversation | core, events, identity/context contracts |
| Planner | core, events; **outputs** of memory/knowledge (not their internals) |
| Prompt | core, events; context DTOs from memory/knowledge/planner |
| Memory | core, events, store/index ports |
| Knowledge | core, events, search/repo ports |
| AI | core, events, `AiProviderInterface` |
| Tools | core, events, permissions decision |
| Permissions | core, events |
| Workflow | core, events, task dispatcher port |
| Workspace | none of PHP packages at runtime (Sprint 13) |
| DressnMore Adapters | Tools contracts + Tenant Domain (outside AOS packages) |

## Forbidden dependencies

| From | To | Why forbidden |
|------|----|----------------|
| Any AOS domain package | Laravel Models / Eloquent | Breaks Hexagonal boundary |
| Any AOS domain package | Channel SDKs / Meta / Twilio | Belongs in adapters |
| Communication adapters | Planner / Tools / Memory internals | Adapters must stay dumb |
| Memory / Knowledge | AI Provider | Retrieval ≠ generation |
| AI Provider | Tools / Conversation / Channels | Provider is completion only |
| Prompt | Tools / DressnMore | Prompt builds text only |
| Tools | Prompt / Provider | Tools execute business ops only |
| Workspace | Direct DB | UI uses ports/APIs later |
| DressnMore Domain | AOS internals | Domain must not know Agent packages |

## Circular dependency detection

### Known safe cycles (none)

There must be **no compile-time package cycles**. Runtime call pairs that look cyclic are sequenced:

| Apparent cycle | Resolution |
|----------------|------------|
| Conversation ↔ Communication | Communication emits messages; Conversation owns state; replies return via outbound port |
| Planner ↔ Tools | Planner plans; Tools execute later; results return as data, not package import cycle |
| Prompt ↔ Memory | Memory produces DTO; Prompt consumes DTO; Memory never imports Prompt |
| Workflow ↔ Tools | Workflow dispatches task ports; Tools do not import Workflow |

### Detection checklist (pre-binding)

1. Package `composer.json` requires only foundation + explicit peer contracts.  
2. No `use DressnMore\Aos\X` from a lower layer into a higher orchestration layer that already depends on it.  
3. Adapters live in Infrastructure and depend inward.  
4. Event payloads are primitives/DTOs — not entity classes from other modules.

## Dependency risk heatmap

| Risk | Rating | Note |
|------|--------|------|
| Workspace mock coupling to fake shapes | Medium | Replace with contract-stable read models |
| Orchestration “god service” emerging | Medium | Keep pipeline stages explicit |
| Tools ↔ Domain leakage | High if rushed | Enforce contracts-only binding |
| Provider SDK entering `aos-ai` | High | Keep plugins outside core package |

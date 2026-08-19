# 12 — Master AI Execution Sequence

Canonical sequence for one customer turn. This is the **integration spine** for DressnMore binding.

```
Customer Message
    ↓
Channel Adapter (WhatsApp / Messenger / …)
    ↓
Communication Hub
    · webhook validate
    · normalize
    · route conversation
    ↓
Identity & Tenant Resolution
    ↓
Conversation Engine
    · load/create
    · ownership check (AI/Human)
    ↓
AI Planner
    · intent / goals / risk
    · planned tools
    ↓
Memory Retrieval  ──┐
                    ├──► Context Pack
Knowledge Retrieval─┘
    ↓
Permission Snapshot
    ↓
Prompt Engine
    · persona / plan / memory / knowledge / safety
    · guard + optimize + version
    ↓
AI Provider Platform
    · select provider/model
    · complete or stream
    · fallback/retry/cost track
    ↓
Decision Branch
    ├─ Reply only ──────────────────────────────┐
    ├─ Tool calls → Permissions → Tool Gateway  │
    │       ↓                                   │
    │   DressnMore Business Adapters            │
    │       ↓                                   │
    │   Tool Results → (optional re-plan)       │
    └─ Escalate / Approval → Workspace HITL ────┤
                                                ↓
Workflow Hooks (optional; trigger-based)
    ↓
Reply Pipeline
    · compose outbound
    · Communication Hub send
    · delivery tracking
    ↓
Analytics Markers
    ↓
Audit Trail
    ↓
Memory Write (classified facts only)
```

## Guarantees of this sequence

1. **No business write** before Permissions + Tool Gateway.  
2. **No provider call** before Prompt Ready.  
3. **No channel SDK** above Communication adapters.  
4. **Human-in-the-loop** can interrupt at Approval / Ownership.  
5. **Audit + Analytics** always observe terminal outcomes.

## Mapping to packages

| Step | Package |
|------|---------|
| Channel → Hub | `aos-communication` |
| Tenant/Identity | context/identity modules |
| Conversation | `aos-conversation` |
| Planner | `aos-planner` |
| Memory | `aos-memory` |
| Knowledge | `aos-knowledge` |
| Prompt | `aos-prompts` |
| Provider | `aos-ai` |
| Permissions | `aos-permissions` |
| Tools | `aos-tools` |
| Workflow | `aos-workflow` |
| Operator HITL | `apps/ai-workspace` |
| Bus/Obs | `aos-events`, `aos-observability` |

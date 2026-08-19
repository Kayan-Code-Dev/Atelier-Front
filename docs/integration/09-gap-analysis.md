# 09 — Gap Analysis

Review of AOS after Sprint 1–13, focused on readiness for DressnMore binding.  
**No redesign proposed unless necessary.**

## 1) Architectural gaps

| Gap | Severity | Notes | Recommended action |
|-----|----------|-------|--------------------|
| End-to-end Orchestrator not a single named application service across packages | Medium | Pipelines exist per module; cross-module choreography is documented more than centralized | Keep explicit application orchestrator at composition root when binding — do not merge domains |
| Real channel adapters absent | Expected | Hub uses stub adapters | Implement WhatsApp adapter first behind existing port |
| DressnMore tool adapters absent | Expected | Contracts exist; implementations pending | Build adapters per catalog, gated by Permissions |
| Workspace still mock-data | Expected | Sprint 13 intentional | Introduce read-model ports; no Domain rewrite |
| Event bus subscriptions thinly wired across modules | Medium | Events defined; not all cross-module subscribers implemented | Wire subscribers at composition root |
| Vector/embeddings knowledge path deferred | Low (by design) | Lexical search only | Keep SearchEngine port; add vector adapter later |

## 2) Missing abstractions (non-blocking if scheduled)

| Abstraction | Why it matters | Status |
|-------------|----------------|--------|
| Outbound Reply Composer (dedicated) | Separates model text from channel formatting | Partially in Communication outbound |
| Projection/read-model layer for Workspace | Prevents UI reaching write models | Missing — needed before production UI ops |
| Dead Letter operational store for Workflow | Retry/DLQ conceptual | Contractual; persistence adapter pending |
| Channel Binding Registry as first-class store | TenantResolver depends on it | Documented; ensure concrete registry before WhatsApp prod |

## 3) Duplicate responsibilities (watch list)

| Overlap | Risk | Guidance |
|---------|------|----------|
| Conversation summary in Memory vs Prompt vs Tool `GenerateConversationSummary` | Confusion | Memory owns durable summaries; Tool is explicit invoke; Prompt consumes results only |
| Knowledge Search tool vs Knowledge Engine retrieval | Dual path | Engine for agent context; Tool for explicit user “search policy” asks — both OK if documented |
| Workflow Notification tasks vs NotifyStaff tool | Parallel | Workflow calls Tool/adapter — do not reimplement notify inside Workflow domain |
| Workspace Analytics vs Observability metrics | Parallel | Workspace shows business KPIs; Observability is technical telemetry |

## 4) Future risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Putting Meta SDK inside `aos-communication` Domain | Boundary collapse | Separate adapter package |
| Calling Eloquent from Tool Gateway | Irreversible coupling | Adapter layer only |
| Letting Full Auto mode skip Critical approvals | Compliance incident | Keep Always-approval tools immutable |
| Prompt injection via channel text | Safety | Prompt Guard + Permissions + redaction |
| Context explosion (memory+knowledge+history) | Cost/latency | Rankers, budgets, compression placeholders → real compressors |

## 5) Scalability risks

| Area | Risk | Mitigation |
|------|------|------------|
| Synchronous full pipeline on webhook thread | Timeouts | Queue intake after normalize (Ingress already conceptual) |
| In-memory stores in modules | Not multi-node safe | Swap store ports before horizontal scale |
| Large Workspace JS bundle | UX latency | Code-split by route when API-wiring starts |
| Multi-tenant noisy neighbors | Fairness | Rate limits in Communication + tenant budgets in AI policies |

## 6) Maintainability risks

| Area | Risk | Mitigation |
|------|------|------------|
| Many packages | Onboarding cost | Keep this integration pack as map |
| Aggregated domain event classes | Discoverability | Prefer one event type per class when hardening |
| Docs vs code drift | False confidence | Gate PRs on Sprint DoD + this pack updates |

## 7) Coupling problems

| Coupling | Status |
|----------|--------|
| Channel → Business | **Avoided** by architecture |
| Provider → Tools | **Avoided** |
| Workspace → PHP packages | **Avoided** today (mock) |
| Tools → Laravel models | **Not started** — highest future coupling risk |

## 8) Necessary vs unnecessary change

### Necessary before DressnMore production binding

1. Tool adapters for the MVP use-case set (customer, reservation, order status, invoice read, knowledge, handover).  
2. Channel Binding Registry + WhatsApp adapter.  
3. Composition-root orchestrator wiring events + pipelines.  
4. Workspace read ports for Inbox/Approvals (replace mocks for those screens).  

### Not necessary (do not redesign)

- Merging Memory + Knowledge into one engine  
- Replacing Hexagonal module layout  
- Introducing a second business API beside Tools  
- Embedding DressnMore UI inside AOS packages  

## Verdict

Architecture is **coherent and contracts-first**. Gaps are primarily **adapter/composition/read-model** work — not foundational redesign.

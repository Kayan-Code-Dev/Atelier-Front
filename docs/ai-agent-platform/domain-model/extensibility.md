# Extensibility Points

Designed places to grow the platform **without rewriting the domain core**.

| Extension point | How you extend | Must not require |
|-----------------|----------------|------------------|
| **New Channel** | New Channel Type + Adapter implementing Channel Port; new Channel Account bindings | Changes to Conversation/Tool invariants |
| **New Tool** | Register Tool in catalog + map Capabilities + optional Approval rules | Channel-specific forks |
| **New Capability** | Add Capability definition + policy UI vocabulary | Hardcoded Orchestrator special cases without policy |
| **New Persona / Prompt Template** | Publish Persona revision / templates | Core FSM changes |
| **New Knowledge Provider** | New Knowledge Source type feeding Documents/Collections | Direct Context mutation bypassing providers |
| **New Automation Trigger** | New Automation Trigger enum + workflow wiring | Embedding trigger logic in Channel Adapters |
| **New Workflow Step type** | Extend step vocabulary in Automation Context | Changing Conversation ownership rules ad hoc |
| **New LLM Provider** | Replace/augment generation behind Orchestrator/Reply ports | Leaking provider SDK into domain entities |
| **New Business Module (Tenant Ops)** | Expose new Tools + Business Object Types via anti-corruption layer | Copying Ops schemas into Agent master data |
| **New Notification channel** | Deliver Notification via existing/new staff notify ports | Coupling notifications to WhatsApp customer channel |
| **New Analytics metric** | Emit new Analytics Event types | Writing analytics that mutate domain state |
| **New Escalation Reason** | Extend Escalation Reason catalog | Handover without reason |
| **New Operating Mode** (rare) | Product ADR + mode overlay rules | Using mode to grant denied capabilities |
| **Training / Eval expansion** | New Learning Records / Datasets / eval suites | Silent self-training in production |

## Extension principles

1. **Open at Ports, closed at Invariants.**  
2. Prefer catalog + policy over code branches.  
3. Every extension must declare Tenant scope and Audit expectations.  
4. Cross-context features integrate through Domain Events.  
5. Architecture Freeze ADRs remain binding (channel-agnostic core, binding isolation, mode≠permission).

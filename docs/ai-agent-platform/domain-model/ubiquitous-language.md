# Ubiquitous Language

Official glossary. **One definition per term.** Prefer these words in specs, tickets, and design reviews.

| Term | Official definition |
|------|---------------------|
| **AI Agent / Digital Employee** | The configured AI worker for a Tenant: persona, mode, capabilities, and accountability — not a chatbot product. |
| **Agent Platform** | The bounded product that operates Digital Employees across channels inside DressnMore. |
| **Tenant** | One atelier workspace; hard isolation boundary for all agent data and actions. |
| **Channel** | A communication medium type (WhatsApp, Web Chat, …). Transport only. |
| **Channel Account** | A concrete connected account on a Channel, bound to exactly one Tenant. |
| **Binding** | Explicit Channel Account → Tenant (and Agent) association used for resolution. |
| **Contact** | Channel-visible identity of the person messaging. |
| **Customer Reference** | Link from Agent Platform to the Tenant Ops customer record. |
| **Conversation** | Operational work unit of interaction with a Contact; includes ownership and state. **Not** “a chat widget.” |
| **Session** | Informal runtime span of continuous processing for a Conversation decision cycle. Prefer **Conversation** + **decision cycle** in formal docs; do not invent a second master entity named Session unless product later requires it. |
| **Message** | One utterance or system notice inside a Conversation. |
| **Ownership** | Who currently may reply/act: AI, Human, or SharedAssist. |
| **Human Staff** | Tenant user collaborating with the Agent. |
| **Human Handover** | Explicit ownership transfer between AI and Human Staff with reason and packet. |
| **Context** | Curated information used for one decision; materialized as a **Context Bundle**. |
| **Context Bundle** | Single assembled pack (policy, persona, memory, customer, facts, knowledge, limits) for Orchestration. |
| **Context Provider** | A source that contributes a slice into a Context Bundle. |
| **Memory** | Conversation-scoped working memory (short history, facts, open tasks). |
| **Summary** | Compressed narrative of a Conversation for handover/archive/analytics. |
| **Knowledge** | Published atelier information used for answers (policies, FAQ, services). |
| **Knowledge Collection / Document** | Organized units of Knowledge. |
| **Persona** | Voice and speaking rules of the Digital Employee. |
| **Prompt Template** | Reusable instruction pattern contributing to generation. |
| **Operating Mode** | Autonomy overlay: Assistant, Hybrid, or Full Auto. |
| **Capability** | Named ability that may be granted to an Agent. |
| **Capability Policy** | The Agent’s allow/deny/approve rules and ceilings. |
| **Permission Ticket** | One-time authorization outcome allowing a specific Tool Execution. |
| **Tool** | Channel-agnostic business action/query into Tenant Ops. |
| **Tool Execution** | One attempted run of a Tool. |
| **Tool Result** | Terminal outcome of a Tool Execution. |
| **Business Object Reference** | Pointer to Tenant Ops entities (invoice, order, product, …). |
| **Approval** | Human gate for a sensitive action (`Approval Request` + `Approval Decision`). |
| **Task** | Trackable work item for staff or follow-up, optionally linked to a Conversation. |
| **Workflow / Automation Workflow** | Triggered multi-step automation scenario. |
| **Workflow Step** | One step inside a Workflow. |
| **Notification** | Alert to staff/operators about an agent-related event. |
| **Audit Record** | Immutable accountability entry. |
| **Analytics Event** | Measurement fact for quality/cost insights. |
| **Confidence** | Assessed certainty of an AI decision (`Score` numeric, `Level` band). |
| **Escalation Reason** | Coded why handover started. |
| **Isolation Key** | Logical token ensuring a pipeline run cannot cross tenants. |
| **Decision Cycle** | One pass: inbound (or wake) → context → decide → tools/handover/reply. |
| **Orchestrator** | Architectural name for the decision brain (not a domain entity customers see). |
| **Tenant Ops** | External system of record for atelier business data. |
| **Learning Record** | Labeled example for improvement; not automatic live self-training. |
| **Training Dataset** | Versioned set of learning material / eval cases. |

## Forbidden synonyms (in formal docs)

| Avoid saying | Say instead |
|--------------|-------------|
| Bot / Chatbot | AI Agent / Digital Employee |
| Chat / Ticket (for core object) | Conversation |
| WhatsApp automation (as the product) | Channel Adapter + Agent Platform |
| Prompt dump / full DB context | Context Bundle |
| User permission (for agent tools) | Capability / Capability Policy |
| CRM lead (unless Platform CRM) | Contact / Customer Reference |

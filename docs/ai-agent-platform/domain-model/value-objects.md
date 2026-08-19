# Value Objects

Value Objects are defined by their attributes. They are immutable in meaning; replacing them means creating a new value.

| Value Object | Meaning | Typical use |
|--------------|---------|-------------|
| **Tenant Id** | Opaque atelier identity | On every aggregate root |
| **Isolation Key** | Logical token binding a pipeline run to a Tenant + Channel Account | Ingress → Tools |
| **Channel Identifier** | `channelType + providerAccountId` | Binding & routing |
| **External Thread Id** | Provider conversation/thread key | Conversation correlation |
| **External Contact Id** | Provider-side person key | Contact identity |
| **Customer Identity** | Normalized phone/email/name keys used for matching | Contact Resolver |
| **Message Content** | Text/media references + safety flags | Conversation Message |
| **Message Envelope** | Normalized inbound/outbound metadata | Channel → Ingress |
| **Conversation State** | Current FSM state value | Conversation |
| **Ownership** | `AI` \| `Human` \| `SharedAssist` | Conversation |
| **Operating Mode** | `Assistant` \| `Hybrid` \| `FullAuto` | Agent + decision overlay |
| **Capability Grant** | Capability key + effect (`allow`/`deny`/`approve`) + optional ceiling | Capability Policy |
| **Money Ceiling** | Currency + max amount for discounts/charges | Policy |
| **Confidence Score** | Numeric 0–1 (or 0–100) model/self assessment | Orchestrator decisions |
| **Confidence Level** | Coarse band derived from score | Handover rules |
| **Context Snapshot** | Immutable assembled Context Bundle content hash/version | Audit + Orchestrator |
| **Persona Configuration** | Name, tone, languages, do/don’t | Persona |
| **Prompt Configuration** | Template id + variable bindings | Reply / Orchestrator |
| **Language** | BCP-47 language tag | Persona, Message |
| **Timezone** | IANA timezone | Hours, automation |
| **Working Hours / Business Hours** | Weekly intervals in a timezone | Automation, quiet hours |
| **Business Object Pointer** | `objectType + objectId` | Tool results, context facts |
| **Permission Ticket** | Opaque allow decision for one tool call | Tool Execution |
| **Escalation Reason Code** | Why handover started | Human Handover |
| **Delivery Status** | sent/delivered/read/failed | Outbound Message |
| **Health Indicator** | ok/degraded/down + reason | Agent Health |

## Modeling notes

- Enumerations (see [enumerations.md](./enumerations.md)) often appear **inside** Value Objects (e.g. Conversation State wraps Conversation Status).
- Value Objects must not hold Tenant-foreign references that bypass Isolation Key checks.
- Prefer replacing a VO over mutating fields in place (conceptual immutability).

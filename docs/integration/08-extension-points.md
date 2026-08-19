# 08 — Extension Points

All extension seams that allow growth **without redesigning** AOS core.

## 1) AI Providers

| Seam | Contract | How to extend |
|------|----------|---------------|
| Provider plugins | `AiProviderInterface` | New package/adapter implementing complete/stream |
| Model catalog | ModelRegistry | Register models/capabilities |
| Selection policy | ProviderSelector / PolicyEngine | Tune cost/latency/compliance weights |

## 2) Channels

| Seam | Contract | How to extend |
|------|----------|---------------|
| Channel adapters | `ChannelAdapterInterface` | WhatsApp/Meta/Telegram/Email real adapters |
| Channel accounts | ChannelRegistry | Multi-tenant bindings |
| Comment flows | CommentFlow policies | New social surfaces |

## 3) Business Tools

| Seam | Contract | How to extend |
|------|----------|---------------|
| Tool adapters | Tool Gateway ports | DressnMore service adapters per contract |
| Capability keys | Permissions matrix | Add capability + mode + approval |
| Tool catalog | Taxonomy + contracts | Versioned contract docs first |

## 4) Knowledge sources

| Seam | Contract | How to extend |
|------|----------|---------------|
| Source adapters | KnowledgeSource types | PDF/web/API ingest adapters |
| Search engine | `KnowledgeSearchEngineInterface` | Swap lexical → vector later |
| Collections/visibility | Policies | New scopes without engine rewrite |

## 5) Workflow tasks & triggers

| Seam | Contract | How to extend |
|------|----------|---------------|
| TaskDispatcher | Task type handlers | AI/Human/Business/Notify/Delay… |
| Triggers | TriggerType + TriggerEngine | New domain triggers |
| Retry/DLQ | RetryManager policies | Ops-tuned policies |

## 6) Policies & permissions

| Seam | Contract | How to extend |
|------|----------|---------------|
| Policy packs | Permission/Policy engine | Tenant policy bundles |
| Operating modes | Assistant/Hybrid/Full Auto | Mode matrices |
| Channel policies | Rate/media/compliance | Per-channel limits |

## 7) Personas & prompt profiles

| Seam | Contract | How to extend |
|------|----------|---------------|
| Persona catalog | Prompt/Persona engine | New employee personas |
| Templates | Prompt templates | Sales/Support/… versions |
| Guards/optimizers | Prompt pipeline stages | New safety/compression stages |

## 8) Memory stores

| Seam | Contract | How to extend |
|------|----------|---------------|
| MemoryStore | `MemoryStoreInterface` | Redis/SQL/etc. adapters |
| MemoryIndex | `MemoryIndexInterface` | Better retrieval indexes |
| Rankers/summarizers | Domain services | Replace rule-based with ML later (still behind ports) |

## 9) Observability & Workspace

| Seam | Contract | How to extend |
|------|----------|---------------|
| Logger/Metrics/Health | Observability ports | Vendor backends |
| Workspace features | Feature modules | New operator screens against same design system |
| Command palette / nav | Navigation config | Register destinations |

## Extension rules (mandatory)

1. **Add by new adapter/package**, not by editing unrelated modules.  
2. **Contracts first** — document before implementation.  
3. **No SDK in domain packages** — SDKs stay in Infrastructure adapters.  
4. **Tenant isolation** must be preserved by every new store/source/channel.

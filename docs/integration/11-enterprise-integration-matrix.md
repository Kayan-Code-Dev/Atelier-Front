# 11 — Enterprise Integration Matrix

| Module | Interacts with | Why | Contracts / Ports | Events (pub → sub) |
|--------|----------------|-----|-------------------|--------------------|
| **AI Workspace** | All engines (future) | Operate digital employees | Future read/command ports | Consumes projections of Approvals, Delivery, Workflow |
| **Communication Hub** | Conversation, Identity, Workflow, Workspace | Ingress/egress all channels | `ChannelAdapterInterface`, WebhookGateway | MessageNormalized → Conversation/Planner; CommentClassified → Workflow |
| **Identity & Context** | Communication, Conversation, Permissions | Tenant + customer binding | TenantBinding, Isolation Guard | TenantResolved → all downstream |
| **Conversation** | Communication, Planner, Memory, Workspace | Operational work unit | Conversation aggregate APIs | OwnershipChanged → Workspace; ConversationUpdated → Memory |
| **Planner** | Conversation, Memory, Knowledge, Prompt, Permissions | Decide goals & tools | ExecutionPlan model | PlanCreated → Prompt/Workspace |
| **Memory** | Conversation, Planner, Prompt, Workspace | Durable classified facts | MemoryStore/Index ports | MemoryRetrieved → Prompt |
| **Knowledge** | Planner, Prompt, Workspace | Published enterprise knowledge | SearchEngine/Repo ports | KnowledgeRetrieved → Prompt |
| **Prompt Engine** | Planner, Memory, Knowledge, AI | Build provider-agnostic prompt | PromptEngineInterface | PromptBuilt → AI orchestration |
| **AI Provider** | Prompt, Observability | LLM completion/stream | `AiProviderInterface` | CompletionReceived → Reply pipeline |
| **Permissions** | Planner, Tools, Workspace | Capability firewall | Policy/capability matrix | ApprovalRequired → Workspace; PermissionDenied → Audit |
| **Business Tool Gateway** | Permissions, DressnMore adapters, Planner | Only business mutation/read path | Tool contracts | ToolExecuted/Failed → Planner/Analytics/Audit |
| **DressnMore Domain** | Tool adapters only | Atelier operations truth | Domain services behind adapters | Domain facts return as ToolResult (not raw Domain events into core) |
| **Workflow** | Events/Triggers, Tools/Notify adapters, Workspace | Automation beyond single turn | TriggerEngine, TaskDispatcher | Workflow/Task events → Monitor/Approvals |
| **Events Bus** | All | Decouple side effects | EventBusInterface | Transport only |
| **Observability** | All | Health/logs/metrics | Logger/Health/Metrics ports | Technical signals |
| **Reply Pipeline** | AI/Tools results, Communication, Audit | Deliver customer response | Outbound dispatcher | MessageSent/Delivered/Failed |

## Why this matrix matters

Any new integration PR must state: **which row it changes**, **which contract**, and **which events** — otherwise it is out of process.

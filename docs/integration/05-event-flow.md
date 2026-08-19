# 05 — Event Flow Catalog

Events are **domain signals**. They must not embed DressnMore persistence details.

Columns: **Publisher** · **Subscribers (conceptual)** · **Business meaning** · **Lifecycle**

---

## Communication events

| Event | Publisher | Subscribers | Business meaning | Lifecycle |
|-------|-----------|-------------|------------------|-----------|
| ChannelRegistered | Communication | Observability, Workspace | New channel account available | Once per registration |
| ChannelConnected | Communication | Identity, Workspace | Channel healthy/usable | On connect |
| ChannelDisconnected | Communication | Workspace, Ops | Channel unavailable | On disconnect/error |
| MessageReceived | Communication | Conversation, Workflow, Analytics | Raw intake accepted | Per inbound |
| MessageNormalized | Communication | Conversation, Planner pipeline | Canonical message ready | Per inbound success |
| ConversationRouted | Communication | Conversation | Conversation key resolved | Per inbound |
| MessageSent | Communication | Delivery, Analytics | Outbound accepted by adapter | Per outbound |
| MessageDelivered | Communication | Conversation, Analytics | Provider delivered | Async |
| MessageRead | Communication | Conversation, Analytics | Read receipt | Async |
| MessageFailed | Communication | Retry/Workflow, Ops | Send/delivery failure | On failure |
| AttachmentUploaded | Communication | Conversation, Knowledge ingest (future) | Media attached | Per media |
| CommentClassified | Communication | Workflow, Conversation | Comment intent decided | Per comment |
| PrivateConversationStarted | Communication | Conversation, Messenger path | Public→private escalation | On escalate |

## Conversation / Identity events

| Event | Publisher | Subscribers | Business meaning | Lifecycle |
|-------|-----------|-------------|------------------|-----------|
| ConversationStarted | Conversation | Memory, Analytics, Workflow | New operational unit | Once |
| ConversationUpdated | Conversation | Memory write pipeline | State/content changed | Frequent |
| OwnershipChanged | Conversation | Workspace, Planner | AI ↔ Human ownership | On handover |
| StateTransitioned | Conversation | Workflow hooks, SLA | Formal state change | On legal transition |
| TenantResolved | Identity | All downstream | Tenant bound | Per message |
| IdentityMatched | Identity | Conversation, Tools | Customer twin known | When matched |
| IsolationViolationRejected | Identity | Security/Audit | Cross-tenant blocked | On reject |

## Planner / Permission / Tool events

| Event | Publisher | Subscribers | Business meaning | Lifecycle |
|-------|-----------|-------------|------------------|-----------|
| PlanCreated | Planner | Prompt, Workspace monitor | Immutable plan ready | Per turn |
| PlanRevised | Planner | Prompt | Plan updated | Rare |
| PlanRejected | Planner | Conversation, Workspace | Cannot plan safely | On reject |
| ApprovalGateInserted | Planner/Permissions | Approvals UI, Workflow | HITL required | On gate |
| PermissionGranted | Permissions | Tools | Capability allowed | Per check |
| PermissionDenied | Permissions | Planner, Audit | Capability blocked | Per check |
| ApprovalRequired | Permissions | Workspace Approvals | Wait for human | Pending |
| ToolExecuted | Tools | Planner, Analytics, Audit | Tool succeeded | Per call |
| ToolFailed | Tools | Planner, Retry/Workflow | Tool failed | Per fail |
| ToolRejected | Tools | Planner, Audit | Validation/auth reject | Per reject |

## Memory / Knowledge / Prompt / AI events

| Event | Publisher | Subscribers | Business meaning | Lifecycle |
|-------|-----------|-------------|------------------|-----------|
| MemoryCreated/Updated/Expired/… | Memory | Prompt (indirect), Workspace explorer | Memory lifecycle | Continuous |
| MemoryRetrieved/Ranked | Memory | Prompt, Planner monitor | Context pack ready | Per retrieval |
| KnowledgePublished/Retrieved/… | Knowledge | Prompt, Workspace | Knowledge lifecycle | Continuous |
| PromptBuilt/Validated/Optimized/Rejected | Prompts | AI Provider orchestration | Prompt pipeline | Per generation |
| PromptGuardTriggered | Prompts | Security/Audit | Unsafe prompt blocked | On guard |
| ProviderSelected/Failed/Fallback… | AI | Observability, Workspace analytics | Provider runtime | Per call |
| CompletionReceived / StreamingCompleted | AI | Reply pipeline | Model output ready | Per call |

## Workflow events

| Event | Publisher | Subscribers | Business meaning | Lifecycle |
|-------|-----------|-------------|------------------|-----------|
| WorkflowStarted/Completed/Paused/Cancelled/Archived | Workflow | Monitor, Workspace Automation | Automation lifecycle | Per run/def |
| TaskStarted/Completed/Failed | Workflow | Monitor, Retry | Task unit progress | Per task |
| ApprovalRequested/Completed | Workflow | Workspace Approvals | Workflow HITL | Pending→done |
| RetryTriggered | Workflow | Monitor, DLQ ops | Retry policy fired | On retry |

## Event flow rules

1. **Publisher owns meaning**; subscribers must tolerate missing optional subscribers.  
2. Events cross modules via **Event Bus port** (`aos.events`) — never via shared DB tables.  
3. UI Workspace consumes **projections**, not raw write models (future).  
4. DressnMore domain events (InvoicePaid, …) enter AOS only as **Triggers** or Tool results — not by importing Domain Event classes into Agent Core.

# Conceptual Relationships

Relationships are logical, not foreign-key designs.

```mermaid
flowchart TB
  Tenant -->|owns| AIAgent
  Tenant -->|owns| ChannelAccount
  Tenant -->|owns| KnowledgeCollection
  Tenant -->|owns| AutomationWorkflow

  ChannelAccount -->|is_of| Channel
  ChannelAccount -->|bound_to| AIAgent
  ChannelAccount -->|originates| Conversation

  AIAgent -->|has| Persona
  AIAgent -->|has| CapabilityPolicy
  AIAgent -->|applies| OperatingMode
  Persona -->|uses| PromptTemplate
  CapabilityPolicy -->|grants| Capability
  Capability -->|gates| Tool

  Conversation -->|belongs_to| Tenant
  Conversation -->|served_by| AIAgent
  Conversation -->|with| Contact
  Contact -->|may_link| CustomerRef
  Conversation -->|contains| ConversationMessage
  Conversation -->|has| ConversationMemory
  Conversation -->|may_have| ConversationSummary
  Conversation -->|uses| ContextBundle
  ContextBundle -->|cites| KnowledgeDocument
  ContextBundle -->|includes| CustomerRef
  KnowledgeCollection -->|contains| KnowledgeDocument

  Conversation -->|may_start| HumanHandover
  HumanHandover -->|assigned_to| HumanStaff
  Conversation -->|may_raise| ApprovalRequest
  ApprovalRequest -->|decided_by| HumanStaff
  ApprovalRequest -->|decided_as| ApprovalDecision

  Conversation -->|may_request| ToolExecution
  Tool -->|executed_as| ToolExecution
  ToolExecution -->|produces| ToolResult
  ToolResult -->|may_cite| BusinessObjectRef

  AutomationWorkflow -->|has| WorkflowStep
  AutomationWorkflow -->|may_create| Task
  Conversation -->|may_create| Task
  Task -->|assigned_to| HumanStaff

  Conversation -->|emits| AuditRecord
  ToolExecution -->|emits| AuditRecord
  Conversation -->|emits| AnalyticsEvent
  HumanHandover -->|emits| Notification
  ApprovalRequest -->|emits| Notification
```

## Relationship catalog (selected)

| Relationship | Cardinality | Rule |
|--------------|-------------|------|
| Tenant owns AI Agent | 1:N | Agent cannot move tenants |
| Tenant owns Channel Account | 1:N | Binding immutable to tenant after activate |
| Channel Account bound to AI Agent | N:1 (optional) | Active account should reference an Active agent |
| Conversation served by AI Agent | N:1 | Snapshot persona/policy may freeze per decision |
| Conversation contains Messages | 1:N | Message cannot move conversations |
| Conversation has Memory | 1:1 | Memory dies with conversation purge |
| Conversation uses Context Bundle | 1:N over time | Each decision cycle may assemble a new bundle |
| Capability Policy grants Capabilities | 1:N | Deny by default if missing |
| Capability gates Tool | N:N catalog | Execution still needs ticket |
| Tool Execution produces Tool Result | 1:0..1 | Present when terminal success/fail |
| Conversation may start Handover | 1:N over life | At most one *active* handover |
| Approval Request has Decision | 1:0..1 | Terminal once |
| Knowledge Collection contains Documents | 1:N | Publish rules apply |
| Automation Workflow has Steps | 1:N | Ordered |
| Workflow/Conversation create Task | N:N | Task links back by id |

## Anti-corruption relationships

| Agent Platform concept | External concept |
|------------------------|------------------|
| Customer Reference | Tenant Ops Customer |
| Business Object Reference | Invoice / Order / Dress / Delivery / … |
| Human Staff | Tenant User |
| Notification delivery | Existing Tenant notification channels |

These are **references**, never dual masters.

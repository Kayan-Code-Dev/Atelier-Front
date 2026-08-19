# AI Agent Domain Model — Master

**Document type:** Conceptual Domain Model (DDD)  
**Platform:** DressnMore AI Agent Platform (Digital Employee OS)  
**Version:** 1.0  

This model describes **what exists in the business language of the platform**, not how it is stored or coded.

## Core metaphor

The platform manages a **Digital Employee** (`AI Agent`) that:

- speaks through **Channels**,
- works inside **Conversations**,
- reasons over a curated **Context Bundle**,
- acts via **Tools** under **Capabilities / Permissions**,
- collaborates with **Human Staff** through **Handover**,
- learns persona/knowledge through **Training** artifacts,
- remains accountable via **Audit** and measurable via **Analytics**.

## Bounded Context map (summary)

```mermaid
flowchart LR
  subgraph identity [IdentityAndTenancy]
    Tenant
    ChannelAccount
    Contact
    CustomerRef
  end

  subgraph agent [AgentCore]
    AIAgent
    Persona
    OperatingMode
    CapabilityPolicy
  end

  subgraph conv [ConversationContext]
    Conversation
    Message
    Memory
    Handover
  end

  subgraph knowledge [KnowledgeContext]
    KnowledgeCollection
    KnowledgeDocument
    ContextBundle
  end

  subgraph tools [BusinessToolsContext]
    Tool
    ToolExecution
    ApprovalRequest
  end

  subgraph channels [ChannelContext]
    Channel
    NormalizedMessage
  end

  subgraph auto [AutomationContext]
    AutomationWorkflow
    Task
  end

  subgraph obs [ObservabilityContext]
    AuditRecord
    AnalyticsEvent
    Notification
  end

  channels --> identity
  identity --> conv
  agent --> conv
  knowledge --> conv
  tools --> conv
  auto --> conv
  conv --> obs
  tools --> obs
```

## Aggregate roots (summary)

| Aggregate Root | Boundary protects |
|----------------|-------------------|
| `AIAgent` | Persona, mode, capability policy, channel bindings for that agent |
| `Conversation` | Messages, ownership, state, memory slices, active handover |
| `KnowledgeCollection` | Documents and publish state within a collection |
| `AutomationWorkflow` | Steps, trigger binding, workflow run state |
| `ToolExecution` | Single execution attempt + result + permission ticket reference |
| `ApprovalRequest` | Requested action + decision lifecycle |
| `ChannelAccount` | Binding to Tenant + provider identifiers |

Full detail: [aggregates.md](./aggregates.md), [entities.md](./entities.md).

## High-level conceptual diagram

```mermaid
flowchart TB
  Tenant --> ChannelAccount
  ChannelAccount --> Channel
  Tenant --> AIAgent
  AIAgent --> Persona
  AIAgent --> CapabilityPolicy
  AIAgent --> OperatingModeVO["OperatingMode VO"]
  ChannelAccount --> Conversation
  Conversation --> ConversationMessage
  Conversation --> ConversationMemory
  Conversation --> ContextBundle
  Conversation --> HumanHandover
  Conversation --> ConversationSummary
  ContextBundle --> KnowledgeDocument
  ContextBundle --> CustomerRef
  AIAgent --> Tool
  CapabilityPolicy --> Capability
  Tool --> ToolExecution
  ToolExecution --> ToolResult
  ToolExecution --> ApprovalRequest
  HumanHandover --> HumanStaff
  Conversation --> Task
  AutomationWorkflow --> Task
  Conversation --> AuditRecord
  ToolExecution --> AuditRecord
  Conversation --> AnalyticsEvent
  Conversation --> Notification
```

## Related architecture

This domain model implements the language of the Architecture Freeze. Module names in architecture map to these contexts — see [../module-catalog.md](../module-catalog.md).

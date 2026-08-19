# Domain Events

Events are facts that **already happened** in the domain. They integrate aggregates without shared databases.

| Event | When it occurs |
|-------|----------------|
| **ChannelAccountConnected** | Binding verified and account becomes Active |
| **ChannelAccountDisconnected** | Account paused/disconnected; new conversations blocked |
| **TenantResolvedForMessage** | Inbound message successfully bound to a Tenant (pipeline fact) |
| **TenantResolutionFailed** | Inbound message could not be bound (dead-letter path) |
| **ConversationStarted** | First accepted message creates a Conversation |
| **ConversationAssigned** | Ownership set/changed (AI/Human/SharedAssist) |
| **ConversationStateChanged** | FSM transition committed |
| **ConversationClosed** | Conversation moved to Closed |
| **ConversationSummarized** | Summary published for handover/resolve |
| **CustomerIdentified** | Contact linked to Customer Reference |
| **CustomerIdentificationFailed** | Resolver could not match; remains UnknownContact |
| **MessageReceived** | Inbound message appended |
| **MessageSent** | Outbound message accepted by Channel path |
| **MessageDeliveryFailed** | Outbound delivery failed terminally |
| **ContextBundleAssembled** | Context Engine finished a versioned bundle |
| **AIResponseGenerated** | Reply content produced for send or for staff suggestion |
| **ToolAuthorized** | Permission ticket issued |
| **ToolDenied** | Permission Engine denied a tool/intent |
| **ToolExecuted** | Tool Execution reached Succeeded |
| **ToolFailed** | Tool Execution reached Failed |
| **ReservationOrBookingSignalCreated** | Tool successfully created a booking-like business effect in Tenant Ops (name reflects atelier language; actual Ops event may differ) |
| **InvoiceGeneratedViaAgent** | Tool successfully created/issued an invoice effect (when allowed) |
| **ApprovalRequested** | Approval Request created |
| **ApprovalGranted** | Human granted approval |
| **ApprovalRejected** | Human rejected or timeout classified as rejection |
| **HumanHandoverStarted** | Ownership moved to Human (or SharedAssist escalation) |
| **HumanHandoverFinished** | Handover closed; ownership returned to AI or conversation resolved |
| **TaskCreated** | Task opened from handover/automation/staff |
| **TaskCompleted** | Task marked Done |
| **WorkflowTriggered** | Automation Workflow run started |
| **WorkflowCompleted** | Automation Workflow run finished successfully |
| **WorkflowFailed** | Automation run failed |
| **KnowledgeUpdated** | Knowledge Document published/superseded |
| **PersonaPublished** | New Persona revision activated on Agent |
| **CapabilityPolicyRevised** | Agent capability policy revision activated |
| **OperatingModeChanged** | Agent mode changed |
| **NotificationEmitted** | Staff/operator notification created |
| **AuditRecordAppended** | Accountability entry written (may be implicit to infra; still a domain fact) |
| **LearningRecordCaptured** | Staff/QA captured a learning example |
| **AgentHealthChanged** | Agent Health band changed |

## Event consumers (conceptual)

| Event class | Typical consumers |
|-------------|-------------------|
| Conversation / Handover | Notifications, Analytics, Audit |
| Tool / Approval | Conversation FSM, Audit, Analytics |
| Knowledge / Persona | Context providers (cache invalidation conceptually) |
| Channel binding | Ingress routing, Agent Health |
| Workflow | Tasks, Notifications, optional Conversation touches |

## Rules

1. Events name past tense facts.  
2. Events carry Tenant id + Isolation Key where applicable.  
3. Events do not carry raw provider secrets.  
4. Cross-context reactions are eventual; aggregate internals stay transactional within their boundary (conceptually).

# 03 — Sequence Diagrams (Conceptual)

All diagrams are **conceptual** — no controllers, HTTP, or SDK calls. They describe AOS module choreography.

---

## 1) New WhatsApp message

```mermaid
sequenceDiagram
  participant WA as WhatsApp Adapter
  participant Hub as Communication Hub
  participant ID as Identity/Tenant
  participant Conv as Conversation
  participant Plan as Planner
  participant Mem as Memory
  participant Know as Knowledge
  participant Prompt as Prompt Engine
  participant AI as AI Provider
  participant Perm as Permissions
  participant Tools as Tool Gateway
  participant Reply as Reply Pipeline

  WA->>Hub: inbound payload
  Hub->>Hub: validate webhook + normalize
  Hub->>ID: resolve tenant/contact
  ID->>Conv: bind + load/create conversation
  Conv->>Plan: snapshot
  Plan->>Mem: retrieve
  Plan->>Know: retrieve
  Mem-->>Prompt: MemoryContext
  Know-->>Prompt: KnowledgeContext
  Plan-->>Prompt: ExecutionPlan
  Prompt->>AI: Prompt Ready
  AI-->>Plan: completion / tool intents
  Plan->>Perm: authorize tools
  Perm->>Tools: allowed calls
  Tools-->>Reply: ToolResults
  Reply->>Hub: outbound NormalizedMessage
  Hub->>WA: send
  Hub-->>Conv: delivery tracking
```

---

## 2) Messenger message

Same as WhatsApp with **Messenger Adapter**. Difference is only adapter + channel account binding; core sequence unchanged (normalization invariant).

---

## 3) Facebook Comment

```mermaid
sequenceDiagram
  participant FB as Facebook Comments Adapter
  participant Hub as Communication Hub
  participant Flow as Comment Flow
  participant Conv as Conversation
  participant Plan as Planner
  participant WF as Workflow
  participant MS as Messenger Adapter

  FB->>Hub: comment payload
  Hub->>Flow: classify comment
  alt Need public reply
    Flow->>Hub: public reply outbound
  end
  alt Escalate private / DM
    Flow->>Conv: start private conversation
    Flow->>WF: trigger Comment workflow (optional)
    Conv->>Plan: continue as Messenger-bound thread
    Plan-->>MS: private reply path via Hub
  else No reply
    Flow-->>Hub: stop
  end
```

---

## 4) Instagram Comment

Identical to Facebook Comment with **Instagram Comments Adapter** and Instagram Direct for private escalation.

---

## 5) Reservation request

```mermaid
sequenceDiagram
  participant Cust as Customer (channel)
  participant Hub as Communication Hub
  participant Conv as Conversation
  participant Plan as Planner
  participant Perm as Permissions
  participant Tools as Tool Gateway
  participant Domain as DressnMore (future)
  participant Reply as Reply Pipeline

  Cust->>Hub: "أريد حجز قياس"
  Hub->>Conv: normalize + route
  Conv->>Plan: intent=create_reservation
  Plan->>Plan: plan FindAvailableSlots → CreateReservation
  Plan->>Perm: check create_booking
  alt Approval required (mode/policy)
    Perm-->>Conv: ApprovalRequested (HITL)
  else Allowed
    Perm->>Tools: FindAvailableSlots
    Tools->>Domain: adapter
    Domain-->>Tools: slots
    Tools->>Domain: CreateReservation
    Domain-->>Tools: reservationRef
    Tools-->>Reply: success
    Reply->>Hub: confirm slots/booking
  end
```

---

## 6) Quotation / pricing request

```
Customer → Hub → Conversation → Planner
  → Knowledge (pricing policies) + Memory (customer prefs)
  → Prompt → Provider
  → Tools: SearchProducts / GetPublishedPricing / GenerateQuotation
  → Permissions (custom pricing / discount gates)
  → optional Approval (ApplyDiscount / high-value AcceptQuotation)
  → Reply with quotation summary
```

---

## 7) Complaint

```
Customer complaint
  → Hub normalize
  → Conversation (ownership may shift)
  → Planner intent=complaint
  → Tools: CreateComplaint (+ CreateTask / NotifyStaff)
  → optional TransferConversation (human handover)
  → Memory: store complaint fact (classified)
  → Reply empathy + case id
  → Audit + Analytics markers
```

---

## 8) Transfer to human employee

```mermaid
sequenceDiagram
  participant Plan as Planner/Orchestrator
  participant Perm as Permissions
  participant Tools as Tool Gateway
  participant Conv as Conversation
  participant Hub as Communication Hub
  participant Staff as Human Operator (Workspace)

  Plan->>Perm: escalate_to_human
  Perm->>Tools: TransferConversation
  Tools->>Conv: ownership=Human
  Conv-->>Staff: appears in Approvals/Inbox (Workspace)
  Staff->>Hub: human reply outbound
  Note over Conv: AI may stay Assistant/Hybrid observer
```

---

## 9) Approval Flow

```mermaid
sequenceDiagram
  participant Plan as Planner/Tools
  participant Perm as Permissions
  participant Appr as Approval Task
  participant WS as AI Workspace
  participant Tools as Tool Gateway

  Plan->>Perm: sensitive action
  Perm-->>Appr: RequireApproval
  Appr-->>WS: Approvals queue
  WS->>Appr: Approve / Reject
  alt Approved
    Appr->>Tools: resume gated tool
    Tools-->>Plan: ToolResult
  else Rejected
    Appr-->>Plan: rejected — revise plan / inform customer
  end
```

---

## 10) Workflow Execution

```mermaid
sequenceDiagram
  participant Trig as Trigger (message/cron/manual)
  participant WE as Workflow Engine
  participant Cond as Condition Engine
  participant TD as Task Dispatcher
  participant Mon as Monitor/Metrics

  Trig->>WE: trigger payload
  WE->>WE: load + validate definition
  WE->>WE: build context/variables
  WE->>Cond: evaluate
  loop Tasks sequential/parallel
    WE->>TD: dispatch task (AI/Human/Business/Notify/Delay…)
    TD-->>WE: success/fail
    alt Fail
      WE->>WE: retry policy / DLQ
    end
  end
  WE->>Mon: record execution
```

## Notes

- Channel differences stop at adapters.  
- DressnMore Domain appears only behind Tool Gateway.  
- Workspace is the HITL surface for Approvals and Human Ownership.

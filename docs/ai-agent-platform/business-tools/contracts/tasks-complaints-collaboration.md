# Contracts — Tasks, Complaints, Collaboration, Notifications

---

## CreateTask

| Field | Content |
|-------|---------|
| Tool Name | `CreateTask` |
| Business Purpose | Open staff work item linked to Conversation |
| Description | Creates Task for Human Staff |
| Business Intent(s) | Complaint, SubmitPaymentProof, EscalateHuman, SilenceTimeout |
| Required Capabilities | `create_internal_task` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | Conversation id; reason |
| Expected Inputs | title; description; priority?; assigneeHint?; conversationRef; idempotencyKey |
| Expected Output | taskId |
| Possible Outcomes | Created / Duplicate / Denied |
| Failure Scenarios | Missing conversation |
| Validation Rules | Non-empty title |
| Approval Requirements | None |
| Human Escalation Rules | Often paired with TransferConversation |
| Audit Events | ToolExecuted |
| Analytics Events | TaskCreated |
| Business Rules | Does not change Ownership alone |
| Security Considerations | Tenant staff only |
| Idempotency Rules | Required |
| Concurrency Considerations | — |
| Side Effects | Task created |
| Dependencies | Task port |
| Related Tools | AssignTask, NotifyStaff, TransferConversation |
| Versioning Notes | v1 |

---

## AssignTask

| Field | Content |
|-------|---------|
| Tool Name | `AssignTask` |
| Business Purpose | Assign task to a staff member |
| Description | Updates assignee |
| Business Intent(s) | Collaboration flows |
| Required Capabilities | `assign_internal_task` |
| Required Permissions | Policy grant |
| Allowed Modes | A Approval? · H · F if allowed |
| Required Context | taskId; staffRef |
| Expected Inputs | taskId; staffRef |
| Expected Output | assigned |
| Possible Outcomes | Assigned / Denied |
| Failure Scenarios | Staff inactive |
| Validation Rules | Staff in tenant |
| Approval Requirements | Optional |
| Human Escalation Rules | — |
| Audit Events | ToolExecuted |
| Analytics Events | TaskAssigned |
| Business Rules | — |
| Security Considerations | — |
| Idempotency Rules | Same assignee no-op |
| Concurrency Considerations | — |
| Side Effects | Assignment |
| Dependencies | CreateTask |
| Related Tools | NotifyStaff |
| Versioning Notes | v1 |

---

## CreateFollowUp

| Field | Content |
|-------|---------|
| Tool Name | `CreateFollowUp` |
| Business Purpose | Schedule follow-up action/task |
| Description | Follow-up reminder for staff or automation |
| Business Intent(s) | SilenceTimeout, LeadQualified paths |
| Required Capabilities | `create_follow_up` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | Conversation/customer |
| Expected Inputs | dueAt; purpose; conversationRef; idempotencyKey |
| Expected Output | followUpId |
| Possible Outcomes | Created / Denied |
| Failure Scenarios | Past dueAt |
| Validation Rules | dueAt future |
| Approval Requirements | None |
| Human Escalation Rules | — |
| Audit Events | ToolExecuted |
| Analytics Events | FollowUpCreated |
| Business Rules | — |
| Security Considerations | — |
| Idempotency Rules | Required |
| Concurrency Considerations | — |
| Side Effects | Follow-up scheduled |
| Dependencies | Automation/Task ports |
| Related Tools | CreateTask |
| Versioning Notes | v1 |

---

## CreateComplaint / UpdateComplaint / ResolveComplaint

| Field | Content |
|-------|---------|
| Tool Name | `CreateComplaint` · `UpdateComplaint` · `ResolveComplaint` |
| Business Purpose | Track complaint case lifecycle |
| Description | Complaint entity ops; resolve usually human |
| Business Intent(s) | Complaint |
| Required Capabilities | `write_complaint` / `resolve_complaint` |
| Required Permissions | Resolve rarely for AI |
| Allowed Modes | Create: A,H,F · Resolve: H/F with Approval/Human |
| Required Context | Conversation; customer |
| Expected Inputs | Create: summary, category · Update: fields · Resolve: resolution notes, approval? |
| Expected Output | complaintId / status |
| Possible Outcomes | Opened / Updated / Resolved / Denied |
| Failure Scenarios | Missing summary |
| Validation Rules | Category enum |
| Approval Requirements | Resolve Yes |
| Human Escalation Rules | Always involve human for resolve |
| Audit Events | ToolExecuted |
| Analytics Events | ComplaintOpened/Updated/Resolved |
| Business Rules | AI empathy ≠ resolution |
| Security Considerations | — |
| Idempotency Rules | Create required |
| Concurrency Considerations | Status machine |
| Side Effects | Complaint record; often TransferConversation |
| Dependencies | CreateTask, NotifyStaff |
| Related Tools | TransferConversation |
| Versioning Notes | v1 |

---

## TransferConversation

| Field | Content |
|-------|---------|
| Tool Name | `TransferConversation` |
| Business Purpose | Start Human Handover / change ownership toward human |
| Description | Collaboration tool emitting handover semantics |
| Business Intent(s) | EscalateHuman, Complaint, RequestManager, LowConfidence paths |
| Required Capabilities | `escalate_to_human` (always conceptually available when Agent Active) |
| Required Permissions | Escalate allowed |
| Allowed Modes | A, H, F |
| Required Context | Conversation; escalation reason |
| Expected Inputs | conversationRef; reasonCode; summary?; assigneeHint? |
| Expected Output | handoverId; ownership=Human |
| Possible Outcomes | Transferred / AlreadyHuman |
| Failure Scenarios | Conversation closed |
| Validation Rules | Reason required |
| Approval Requirements | None |
| Human Escalation Rules | This **is** escalation |
| Audit Events | HumanHandoverStarted |
| Analytics Events | Escalated |
| Business Rules | Packet must include summary |
| Security Considerations | — |
| Idempotency Rules | Active handover dedupe |
| Concurrency Considerations | Ownership lock |
| Side Effects | Ownership change; NotifyStaff |
| Dependencies | Conversation aggregate |
| Related Tools | CreateTask, NotifyStaff, GenerateConversationSummary |
| Versioning Notes | v1 |

---

## RequestApproval / SubmitApprovalDecision

| Field | Content |
|-------|---------|
| Tool Name | `RequestApproval` · `SubmitApprovalDecision` |
| Business Purpose | Gate sensitive Tool executions |
| Description | ApprovalRequest lifecycle tools |
| Business Intent(s) | Any RequireHumanApproval mapping |
| Required Capabilities | `request_approval` / staff `decide_approval` |
| Required Permissions | Agent may request; staff decides |
| Allowed Modes | All for Request; Submit by Human Staff / system timeout |
| Required Context | proposedTool; conversation |
| Expected Inputs | Request: toolName, argsHash, reason · Submit: approvalId, decision, actor |
| Expected Output | approvalId / decision |
| Possible Outcomes | Requested / Granted / Rejected / TimedOut |
| Failure Scenarios | Duplicate open approval |
| Validation Rules | One open per action key |
| Approval Requirements | N/A (meta) |
| Human Escalation Rules | Timeout → Escalate |
| Audit Events | ApprovalRequested/Granted/Rejected |
| Analytics Events | Approval* |
| Business Rules | No silent grant on timeout |
| Security Considerations | Staff auth for Submit |
| Idempotency Rules | Request keyed |
| Concurrency Considerations | Single decision |
| Side Effects | PendingApproval state |
| Dependencies | Permission Engine |
| Related Tools | All sensitive writes |
| Versioning Notes | v1 |

---

## NotifyStaff / NotifyCustomer

| Field | Content |
|-------|---------|
| Tool Name | `NotifyStaff` · `NotifyCustomer` |
| Business Purpose | Send operational notifications |
| Description | Staff in-app/email; customer via allowed channel send path |
| Business Intent(s) | Escalation, reminders, confirmations |
| Required Capabilities | `notify_staff` / `notify_customer` |
| Required Permissions | Policy grant; customer notify often tied to reply path |
| Allowed Modes | A (staff ok; customer restricted) · H · F |
| Required Context | recipients; template purpose |
| Expected Inputs | audience; templateKey; payload; conversationRef? |
| Expected Output | notificationId / accepted |
| Possible Outcomes | Accepted / Failed / Denied |
| Failure Scenarios | Channel send fail |
| Validation Rules | Template allow-list |
| Approval Requirements | Customer marketing-like notify may require opt-in rules |
| Human Escalation Rules | Fail → staff alert |
| Audit Events | NotificationEmitted |
| Analytics Events | NotificationSent |
| Business Rules | Not for arbitrary spam |
| Security Considerations | No secret leakage in payload |
| Idempotency Rules | Recommended |
| Concurrency Considerations | — |
| Side Effects | Notification delivered attempt |
| Dependencies | Notification service |
| Related Tools | TransferConversation, CreateReservation |
| Versioning Notes | v1 |

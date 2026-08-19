# AI Decision Matrix

Canonical actions the Orchestrator may choose in a decision cycle (Architecture + Domain Model).

## Actions

| Action | Meaning |
|--------|---------|
| **Reply** | Send customer-facing answer (or staff suggestion in Assistant Mode) |
| **Clarify** | Ask a question; no side-effect tools |
| **Execute Tool** | Run one or more Tools with Permission Ticket |
| **Request Approval** | Open ApprovalRequest; wait |
| **Escalate** | Start Human Handover |
| **Reject** | Refuse unsafe/unsupported ask without doing it |
| **End Conversation** | Move toward Resolved/Closed when appropriate |

## Decision matrix

| Condition | Primary action | Notes |
|-----------|----------------|-------|
| Intent clear + High conf + read tool needed + Allow | Execute Tool → Reply | Ground reply on Tool Result |
| Intent clear + High conf + answer in Context/KB only | Reply | Cite knowledge |
| Intent clear + Medium conf + missing slot | Clarify | Max 1–2 clarifies then Escalate |
| Intent Low conf | Clarify once → Escalate if still Low | Never mutate |
| Write tool Allow + High conf + Mode allows send | Execute Tool → Reply | |
| Write tool RequireHumanApproval | Request Approval | Optional draft reply |
| Write tool Deny | Reject + Escalate | Explain limit |
| Explicit EscalateHuman / Manager | Escalate | Immediate |
| Complaint / Safety / anger signals | Escalate | Empathy Reply optional first |
| RequestDiscount beyond published | Reject custom + Escalate/Approve path | |
| UnsupportedService | Reject (polite) + Reply alternatives | |
| ConflictingIntents | Clarify → Escalate | |
| Ownership=Human | No autonomous customer Reply/Tools | SharedAssist may Suggest only |
| PendingApproval open | Wait / notify; no duplicate exec | |
| Quiet hours + write | Request Approval or Escalate | Per policy |
| Goodbye + no open work | Reply + End Conversation | |
| Tool failure repeated | Escalate | |
| Channel binding fail | No tenant action | Platform dead-letter |
| Assistant Mode | Prefer Reply-as-suggestion / Approval | Restrict auto send |
| Full Auto + sensitive financial | Still Approval/Escalate | Invariants win |

## Priority when multiple apply

1. Safety / Block  
2. Explicit human request  
3. Permission Deny / Approval required  
4. Ownership already Human  
5. Confidence gates  
6. Happy-path Tool/Reply  

## Mapping to Domain Events (examples)

- Reply → `AIResponseGenerated`, `MessageSent`  
- Execute Tool → `ToolAuthorized` / `ToolExecuted` / `ToolFailed` / `ToolDenied`  
- Request Approval → `ApprovalRequested`  
- Escalate → `HumanHandoverStarted`  
- End Conversation → `ConversationClosed` / `ConversationSummarized`

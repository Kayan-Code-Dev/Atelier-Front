# Conversation State Machine

Formal lifecycle from first accepted message to archival close.

```mermaid
stateDiagram-v2
  [*] --> New
  New --> ActiveAI: first_message_accepted
  New --> ActiveHuman: force_human_policy
  ActiveAI --> AwaitingCustomer: reply_sent
  AwaitingCustomer --> ActiveAI: customer_reply
  ActiveAI --> ToolRunning: tool_call
  ToolRunning --> ActiveAI: tool_done
  ToolRunning --> PendingApproval: needs_approval
  PendingApproval --> ActiveAI: approved
  PendingApproval --> ActiveHuman: rejected_or_timeout
  ActiveAI --> ActiveHuman: escalate
  ActiveHuman --> ActiveAI: return_to_ai
  ActiveHuman --> AwaitingCustomer: staff_reply
  ActiveAI --> Resolved: done
  ActiveHuman --> Resolved: done
  Resolved --> Closed: settle
  ActiveAI --> Snoozed: defer
  Snoozed --> ActiveAI: wake
  ActiveAI --> Blocked: abuse_or_legal
  Blocked --> Closed: admin_close
  Closed --> [*]
```

## State meanings

| State | Meaning |
|-------|---------|
| `New` | First message accepted after binding |
| `ActiveAI` | Digital employee owns the conversation |
| `AwaitingCustomer` | Reply sent; waiting on customer |
| `ToolRunning` | Business tool in flight |
| `PendingApproval` | Waiting on staff for a sensitive action |
| `ActiveHuman` | Human owns replies |
| `Snoozed` | Waiting on external event (e.g. delivery tomorrow) |
| `Resolved` | Operationally finished |
| `Closed` | Archived |
| `Blocked` | Safety / legal stop |

## Transition guards (conceptual)

- Illegal transitions are rejected and audited.
- `escalate` always records reason code.
- `return_to_ai` requires Human ownership.
- `Closed` is terminal except controlled reopen policies (future), not Phase 1.

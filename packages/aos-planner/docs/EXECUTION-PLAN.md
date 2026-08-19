# Execution Plan

`PlatformExecutionPlan` is the Sprint 18 planning artifact (Gateway input later).

## Fields

- Plan ID, Tenant ID, Conversation ID
- Goal, Intent
- Required Capabilities, Selected Tools, Ordered Steps
- Required Approvals
- Estimated Cost / Complexity
- Planning Status, Created At, Rejection Reason

## Status

| Status | Meaning |
|--------|---------|
| `ready` | Safe to hand to Gateway |
| `requires_approval` | Plan built; approvals outstanding |
| `rejected` | Policy / permission / subscription / selection failure |
| `failed` | Build failure |
| `draft` | Reserved |

`isReadyForGateway()` is true for `ready` and `requires_approval`.

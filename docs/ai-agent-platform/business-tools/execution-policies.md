# Tool Execution Policies

Platform-level policies applied by Business Tool Gateway (conceptual defaults; Tenant may tighten, not loosen Critical rules).

## Maximum parallel Tools

- **Default:** sequential execution within a plan (safer for atelier consistency).  
- **Parallel allowed:** independent **Low-risk reads** only (e.g. GetBusinessHours + ListPublishedOffers).  
- **Never parallel:** writes on same aggregate/object; any Critical tool with anything else mutating.

## Retry Policy

| Class | Retries | Backoff |
|-------|---------|---------|
| Read Low | 2 | Short |
| Write Medium | 1 (idempotent only) | Short |
| Critical | 0 automatic; human/approval path | — |
| Validation/Deny failures | 0 | — |

## Timeout Policy

- Each Tool declares soft timeout budget conceptually.  
- On timeout → ToolFailed → no assumed success.  
- Writes must be idempotent so client-safe replan possible.

## Fallback Policy

1. Safer read alternative if exists  
2. Clarify  
3. TransferConversation / CreateTask  
4. Never escalate privilege

## Rate Limiting

- Per Tenant + Agent + Tool class.  
- SearchCustomer / SearchProducts anti-enumeration stricter.  
- Exceed → ToolDenied soft + staff alert if sustained.

## Circuit Breaker

- Repeated failures on an Ops port → open circuit for Tool group.  
- Planner sees Tool unavailable → Escalate/NotifyStaff.  
- Half-open probe with reads first.

## Caching Strategy

| Allowed | Not allowed |
|---------|-------------|
| Knowledge snippets short TTL | Balances, availability, slots as long-lived truth |
| Settings hours short TTL | Payment proofs, approvals |
| Transcript of same mediaRef | Assuming hold still valid without recheck |

## Conflict Resolution

- Optimistic concurrency tokens on bookings/holds.  
- Conflict outcome → replan FindAvailableSlots; do not force overwrite.  
- Last-write-wins only for notes/measurements with audit.

## Mode overlays

- **Assistant:** writes create Approval or staff draft; parallel writes off.  
- **Hybrid:** matrix defaults.  
- **Full Auto:** still obey Critical always-approve; circuit breaker stricter alerts.

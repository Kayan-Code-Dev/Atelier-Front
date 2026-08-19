# Tool Selection Rules

How the Digital Employee chooses Tools — aligned with Use Cases Intent Mapping and Decision Matrix.

## Selection algorithm (conceptual)

1. Detect intents + confidences.  
2. Map each intent → candidate Tools from Intent Mapping.  
3. Filter by Discovery (enabled + mode + capabilities present).  
4. Order by Dependency Graph.  
5. For each candidate: if confidence < threshold → Clarify (no tool) or Escalate.  
6. Prefer **least side-effect** Tool that satisfies the need (read over write).  
7. Emit plan.

## Multi-tool selection

- Union of required tools across intents.  
- Deduplicate identical tools (one GetCustomerProfile).  
- Share outputs (customerRef) as inputs to later steps.

## Conflict handling

| Conflict | Rule |
|----------|------|
| Two writes on same Business Object | Serialize or Clarify; never parallel conflicting writes |
| Cancel + Create same reservation | Clarify intent priority |
| Discount + AcceptQuotation | Approval path; Sales human if negotiation |
| Knowledge answer vs Tool fact disagree | Prefer Tool/domain fact; cite both carefully |

## Failure handling

| Failure | Next action |
|---------|-------------|
| NotFound on identity | Clarify identifiers → SearchCustomer again → Escalate |
| NoneAvailable slots | Offer alternatives / CreateFollowUp / Escalate |
| ToolFailed transient | Retry per policy then Escalate |
| ToolDenied | Explain + TransferConversation if customer still needs outcome |
| Ambiguous product | ResolveProduct clarify loop (max N) |

## Retry selection

- Retries use **same Tool + idempotencyKey** for writes.  
- Do not switch to a more dangerous Tool as “fallback” (e.g. never MarkInvoicePaid after RegisterPaymentProof failure).  
- Fallback may be **TransferConversation** or read-only explanation.

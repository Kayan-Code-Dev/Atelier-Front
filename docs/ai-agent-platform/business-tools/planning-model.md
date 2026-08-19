# Tool Planning Model

The AI **Planner** (part of Orchestrator) may invoke **multiple Tools in one decision cycle** when the customer utterance contains multiple intents or a journey needs prerequisites.

## Plan structure (conceptual)

```text
User utterance
  → Intent set (+ confidence)
  → Context Bundle
  → Plan { steps[] }
  → For each step: Lifecycle (validate→auth→exec)
  → Stop on Approval/Deny/Fatal failure per policy
  → Reply Generator uses all Tool Results
```

## Multi-intent example

**Customer:** "أريد معرفة المتبقي عليّ وأحجز بروفة."

| Step | Tool | Why |
|------|------|-----|
| 1 | Identify customer (`SearchCustomer` → `GetCustomerProfile`) | Prerequisite for balance & booking |
| 2 | `GetOutstandingBalance` | First intent |
| 3 | `FindAvailableSlots` | Booking prerequisite |
| 4 | `CreateReservation` | Second intent (may Approval) |
| 5 | Generate Response | Not a Tool — compose facts |

## Planning rules

1. **Reads before writes** in the same plan.  
2. **Identity before money/status**.  
3. **Availability before hold/book**.  
4. **Approval gate stops further writes** in the plan (reads may finish).  
5. **Max steps** per turn per Execution Policies.  
6. If intents conflict (cancel + book same slot) → Clarify, do not run both writes.  
7. Partial success: report what succeeded; Escalate/Clarify remainder.  

## Plan abort conditions

- ToolDenied on a required step  
- PendingApproval on a write  
- Ownership becomes Human mid-plan  
- SafetyViolation  
- Isolation Key invalid  

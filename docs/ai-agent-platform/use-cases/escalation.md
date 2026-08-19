# Human Escalation Catalog

Escalation creates **Human Handover** (and usually a Task + Notification). Reason codes align with Domain Enum `EscalationReason`.

| Escalation case | Reason code | Typical intents/UCs | AI before escalate |
|-----------------|-------------|---------------------|--------------------|
| Low confidence | `LowConfidence` | Any | Clarify once; then escalate |
| Explicit human request | `CustomerRequestedHuman` | EscalateHuman | Immediate handover |
| Manager requested | `StaffForceTake` / exception | RequestManager | Immediate |
| Complaint / anger | `Safety` or complaint path | Complaint | Empathy then handover |
| Discount beyond published | `HighRiskFinancial` | RequestDiscount | Offer published only |
| Exception / special treatment | policy exception | RequestException | No grant |
| Financial mutation | `HighRiskFinancial` | CancelOrder, ApplyDiscount, mark paid | Never silent |
| Permission denied for needed tool | `PermissionDenied` | Any write | Explain + handover |
| Requires approval timeout | `RequiresApproval` | PendingApproval stale | Notify staff / handover |
| Tool repeated failure | `ToolFailure` | TrackOrder, bookings | Apologize + handover |
| Conflicting intents | `AmbiguousIntent` | ConflictingIntents | Clarify; else handover |
| Identity / phone change risk | `HighRiskFinancial`/security | ChangePhoneNumber | Verify or handover |
| Quiet hours human-only policy | `PolicyQuietHours` | Writes at night | Queue Task |
| Media cannot be processed | `AmbiguousIntent` | Voice/PDF/Image | Ask text or handover |
| Payment proof settlement | `RequiresApproval` | SubmitPaymentProof | Ack + human verify |
| Safety / abuse / threat | `Safety` | SafetyViolation | Block + staff alert |
| VIP high-touch policy | tenant policy | RentDress, Tailoring | Optional early handover |
| Staff force take | `StaffForceTake` | UC-HUM-02 | Stop AI replies |

## Non-escalation (stay with AI)

- Published knowledge Q&A with High confidence  
- Read-only status/balance/availability with successful tools  
- Greeting/goodbye/small-talk within persona  
- Reminders/feedback prompts (automation) unless negative feedback  

## Escalation packet ( Domains )

Must include: Summary, intents, last tools, reason code, suggested reply, CustomerRef if any.

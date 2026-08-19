# Human Handover & Collaboration

## Purpose

Treat human staff as first-class coworkers of the digital employee. Ownership of a conversation is explicit and auditable.

## Ownership values

| Ownership | Meaning |
|-----------|---------|
| `AI` | Digital employee owns replies |
| `Human` | Staff owns replies |
| `SharedAssist` | AI drafts; human approves send |

## When AI replies

- Intent within permissions and current mode
- Sufficient confidence and no policy conflict
- No explicit request for a human
- No sensitive financial/destructive operation that is denied

## When escalate to human

- Customer asks for a person
- High-risk intent (refunds, deletes, large discounts, disputes)
- Permission result is `deny` or `require_human_approval`
- Repeated tool failures / critical missing data
- Anger / threat / safety classifier hit
- Quiet-hours policy requiring humans only (optional owner setting)
- Assistant Mode: propose only (or send draft to staff per setting) — not autonomous customer send

## When AI resumes

- Staff explicitly returns ownership (`Return to AI`)
- Auto-resume timeout policy fires
- Pending human task completes and is marked “AI can continue”
- **Never** resume if state is `Closed`, `Blocked`, or contact is banned

## Handover packet (conceptual)

Staff receives:

- Short summary
- Detected intent
- Last tool results
- Escalation reason
- Suggested reply (optional)

## Collaboration with notifications

Handover emits events to Notification Service (in-app / email) so atelier users see an actionable queue — without coupling queue logic to any channel adapter.

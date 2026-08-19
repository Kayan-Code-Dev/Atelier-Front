# Domain Invariants

Rules that **must not break**. Violations are bugs, not configuration preferences.

## Tenancy & isolation

1. Every Conversation, Message, Memory, Tool Execution, Knowledge Document usage, and Audit Record belongs to **exactly one Tenant**.  
2. A Channel Account binds to **exactly one Tenant**; binding Tenant is immutable after activation.  
3. Tenant for a pipeline run is taken **only** from Channel Binding — never inferred from message text.  
4. Every Tool Execution and Context assembly must carry a matching **Isolation Key**; mismatch is a hard failure.  
5. No aggregate may reference another Tenant’s identities.

## Conversation integrity

6. Every Message belongs to **exactly one** Conversation.  
7. A Conversation has **exactly one** Ownership value at any moment (`AI` | `Human` | `SharedAssist`).  
8. Illegal FSM transitions are rejected and audited.  
9. `Closed` / `Blocked` conversations do not accept new AI write Tools.  
10. At most one **active** Human Handover exists per Conversation.

## Agent & permissions

11. An AI Agent cannot execute a Tool outside its Capability Policy.  
12. Deny by default: missing capability ⇒ Deny.  
13. Operating Mode **never expands** permissions; it can only further restrict autonomy.  
14. `RequireHumanApproval` actions cannot execute without a Granted Approval Decision.  
15. Retired/Paused agents cannot send customer replies on Active Channel Accounts.

## Context & tools

16. Tool Execution for a decision cycle requires a Context Bundle assembled for that cycle (or an explicit system exception path that still audits why).  
17. Context Bundle must not include fields forbidden by Capability Policy.  
18. Tool Result is immutable once terminal.  
19. Denied Tool Executions are still recorded (no silent discard).

## Handover & approval

20. Starting Human Handover records an Escalation Reason.  
21. Returning to AI requires finishing the active Handover (explicit finish).  
22. Approval Decision is terminal and singular per Approval Request.  
23. Approval timeout never silently grants.

## Knowledge & training

24. Only **Published** Knowledge Documents are eligible for customer-facing Context.  
25. Learning Records do not auto-mutate production Persona without a publish step.

## Accountability

26. Every Tool Execution (success/fail/deny) appends an Audit Record.  
27. Every Ownership change appends an Audit Record.  
28. Every Approval Decision appends an Audit Record.  
29. Audit Records are append-only (aside from controlled legal redaction procedures).

## Channel

30. Adapters must not call Tenant Ops Tools directly — only via Agent pipeline.  
31. Business Tools must not depend on Channel Type for business decisions (channel limits may constrain reply shaping only).

## Reference integrity

32. Customer Reference and Business Object Reference never become dual masters of Tenant Ops data.  
33. Deleting/unlinking a reference must not delete the Tenant Ops entity.

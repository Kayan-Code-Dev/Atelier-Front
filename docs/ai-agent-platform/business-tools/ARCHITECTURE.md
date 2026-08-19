# Business Tool Architecture (Conceptual)

Official relationship between planning, contracts, permissions, execution, and observability.

```mermaid
flowchart TB
  subgraph agent [AgentCore]
    Planner[AIPlanner]
    Orch[AIOrchestrator]
    Ctx[ContextEngine]
  end

  subgraph gateway [BusinessToolGateway]
    Disco[Discovery]
    Val[Validation]
    AuthZ[AuthorizationBridge]
    Exec[BusinessToolExecutor]
    Contracts[BusinessToolContractsCatalog]
  end

  PE[PermissionEngine]
  Domain[TenantBusinessDomain]
  Audit[AuditTrail]
  An[Analytics]
  Notif[Notifications]

  Orch --> Planner
  Ctx --> Planner
  Planner -->|"plan steps"| Disco
  Disco --> Contracts
  Disco --> Val
  Val --> AuthZ
  AuthZ --> PE
  PE -->|"ticket Allow/Deny/Approve"| AuthZ
  AuthZ --> Exec
  Exec --> Domain
  Exec --> Audit
  Exec --> An
  Exec -->|"ToolResult"| Planner
  AuthZ -->|"PendingApproval"| Notif
  Exec -->|"failures/escalation hooks"| Notif
```

## Boundary statements

1. **Planner** selects Tools; never calls Domain directly.  
2. **Contracts Catalog** is the schema of truth for Tool names/versions.  
3. **Permission Engine** is the only authority for tickets.  
4. **Executor** is the only component that crosses into Tenant Business Domain.  
5. **Audit/Analytics** observe every terminal Tool attempt.  
6. **Notifications** serve Approval and failure collaboration — not business mutation.

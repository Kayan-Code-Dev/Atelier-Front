# Business Tools Philosophy

## Core rule

The AI Agent **never** talks to:

- Databases  
- ORM Models  
- Domain Services directly  
- Controllers / HTTP endpoints as “business actions”  
- Channel adapters for business mutation  

The AI Agent talks **only** to **Business Tools** through the **Business Tool Gateway**.

```text
AI Orchestrator / Planner
        ↓
Permission Engine  →  Permission Ticket
        ↓
Business Tool Gateway
        ↓
Tool Contract validation
        ↓
Tool Executor  →  Tenant Business Domain (anti-corruption)
        ↓
Tool Result + Audit + Analytics
```

## Why Tools

| Principle | Meaning |
|-----------|---------|
| **Encapsulation** | Tenant Ops complexity stays behind a stable contract |
| **Least privilege** | Every call is capability-checked |
| **Auditability** | Every execution is an employee-grade act |
| **Channel agnosticism** | Tools do not know WhatsApp vs Web |
| **Safety** | Side effects are explicit, versioned, and classifiable by risk |
| **Replaceability** | Domain implementation can change; contracts stay |

## What a Tool is

A Tool is a **named, versioned, permissioned business operation** with:

- Declared intents it serves  
- Conceptual inputs/outputs  
- Side effects  
- Approval / escalation rules  
- Idempotency and failure semantics  

## What a Tool is not

- Not a chat reply  
- Not a prompt template  
- Not a raw SQL/query handle  
- Not a free-form “call any service” escape hatch  

## Gateway responsibilities

1. Accept only catalogued Tool names + versions  
2. Demand Isolation Key + Tenant scope  
3. Demand Permission Ticket matching the Tool’s capabilities  
4. Validate inputs against contract rules  
5. Execute via Tenant Ops ports  
6. Return structured Tool Result  
7. Emit Audit + Analytics events  
8. Never leak internal stack traces to the customer path  

## Relationship to prior docs

- **Architecture:** Business Tools Gateway module  
- **Domain Model:** `Tool`, `ToolExecution`, `ToolResult`, `Permission Ticket`  
- **Use Cases:** Intent Mapping lists which Tools each intent may call  

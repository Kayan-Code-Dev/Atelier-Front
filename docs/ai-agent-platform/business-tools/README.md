# Business Tool Contracts — Index

**Status:** Official contract between AI Agent Platform and Tenant Business Domain  
**Depends on:**
- [Enterprise Architecture](../ENTERPRISE-ARCHITECTURE.md)
- [Domain Model](../domain-model/README.md)
- [Use Cases](../use-cases/README.md)

**Non-goals:** APIs, Controllers, Laravel Services, Database schemas, Implementation code.

## Purpose

Define every **Business Tool** the Digital Employee may invoke — the only allowed interface from Agent Core into Tenant Ops.

## Pack contents

| Document | Purpose |
|----------|---------|
| [PHILOSOPHY.md](./PHILOSOPHY.md) | Why Tools exist; anti-patterns |
| [TAXONOMY.md](./TAXONOMY.md) | Tool groups |
| [contracts/](./contracts/) | Full contracts (25 fields each) |
| [capability-matrix.md](./capability-matrix.md) | Capabilities · Modes · Approval · Risk |
| [dependency-graph.md](./dependency-graph.md) | Tool → Tool dependencies |
| [lifecycle.md](./lifecycle.md) | Discovery → Completion |
| [planning-model.md](./planning-model.md) | Multi-tool plans in one turn |
| [selection-rules.md](./selection-rules.md) | How AI selects tools |
| [execution-policies.md](./execution-policies.md) | Retry, timeout, circuit breaker, … |
| [risk-classification.md](./risk-classification.md) | Low → Critical |
| [extensibility.md](./extensibility.md) | Add / version / retire tools |
| [ARCHITECTURE.md](./ARCHITECTURE.md) | Gateway architecture diagram |

## Naming

Tools use **PascalCase verb+noun** conceptual names (e.g. `GetOrderStatus`). These are contract identities — not class or route names.

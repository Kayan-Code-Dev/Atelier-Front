# AI Agent Use Cases — Index

**Status:** Official System Behavior Specification  
**Depends on:**
- [Enterprise Architecture](../ENTERPRISE-ARCHITECTURE.md) (Architecture Freeze)
- [Domain Model](../domain-model/README.md) (Conceptual DDD)

**Non-goals:** Code, database, APIs, UI. No redesign of Architecture or Domain Model.

## Purpose

Define realistic operational scenarios for the Digital Employee inside atelier tenants — the behavioral contract for Backend and AI Engineering.

## Pack contents

| Document | Purpose |
|----------|---------|
| [USE-CASES.md](./USE-CASES.md) | Master overview + group map |
| [catalog/](./catalog/) | Full use cases by group (18-field spec) |
| [intents.md](./intents.md) | Business Intents catalog |
| [intent-mapping.md](./intent-mapping.md) | Intent → Tools / Permissions / Thresholds |
| [escalation.md](./escalation.md) | Human Escalation catalog |
| [automation-opportunities.md](./automation-opportunities.md) | Full / Hybrid / Human automation rating |
| [decision-matrix.md](./decision-matrix.md) | When AI Replies / Clarifies / Tools / Escalates |
| [outcomes.md](./outcomes.md) | Conversation Outcomes vocabulary |
| [roadmap.md](./roadmap.md) | MVP → Enterprise by business value |
| [priority-matrix.md](./priority-matrix.md) | Value, complexity, ROI, frequency, order |

## Conventions

- Language follows [Ubiquitous Language](../domain-model/ubiquitous-language.md).
- Default Operating Mode in flows: **Hybrid** (Architecture default).
- Tools are conceptual names aligned with Business Tools Gateway — not APIs.
- Capabilities referenced by policy keys (Permission Engine).
- Confidence: `High` ≥ 0.85 · `Medium` ≥ 0.65 · `Low` < 0.65 (conceptual bands).

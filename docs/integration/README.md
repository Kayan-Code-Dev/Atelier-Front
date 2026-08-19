# AOS System Integration Review — Index

**Status:** Official integration reference (AOS complete; DressnMore binding started)  
**Scope:** Architecture review only — no code, no database, no APIs, no UI  
**Covers:** Sprint 1 → Sprint 13 (AOS) + Sprint 14 binding entrypoint  
**Depends on:** Enterprise Architecture, Domain Model, Use Cases, Business Tool Contracts, Sprint DoDs

## Purpose

Produce the **authoritative map** that connects every AOS module, clarifies contracts and events, and scores readiness before DressnMore business-domain binding.

**Sprint 14–17 entrypoints:**
- [`packages/dressnmore-customer-binding`](../../packages/dressnmore-customer-binding/README.md) — Customer Domain Binding
- [`packages/dressnmore-reservation-binding`](../../packages/dressnmore-reservation-binding/README.md) — Reservation Domain Binding
- [`packages/aos-tool-registry`](../../packages/aos-tool-registry/README.md) — AI Tool Registry & Capability Platform
- [`packages/aos-tenant-ai`](../../packages/aos-tenant-ai/README.md) — AI Tenant Integration Platform

See [SPRINT14](../ai-agent-platform/aos/SPRINT14-DEFINITION-OF-DONE.md) · [SPRINT15](../ai-agent-platform/aos/SPRINT15-DEFINITION-OF-DONE.md) · [SPRINT16](../ai-agent-platform/aos/SPRINT16-DEFINITION-OF-DONE.md) · [SPRINT17](../ai-agent-platform/aos/SPRINT17-DEFINITION-OF-DONE.md) · [SPRINT18](../ai-agent-platform/aos/SPRINT18-DEFINITION-OF-DONE.md) · [SPRINT18A](../ai-agent-platform/aos/SPRINT18A-DEFINITION-OF-DONE.md) · [SPRINT20](../ai-agent-platform/aos/SPRINT20-DEFINITION-OF-DONE.md) · [SPRINT21](../ai-agent-platform/aos/SPRINT21-DEFINITION-OF-DONE.md).

## Pack contents

| # | Document | Purpose |
|---|----------|---------|
| 01 | [system-map.md](./01-system-map.md) | End-to-end system map |
| 02 | [module-interactions.md](./02-module-interactions.md) | Per-module I/O, deps, events |
| 03 | [sequence-diagrams.md](./03-sequence-diagrams.md) | Conceptual sequences |
| 04 | [business-tool-catalog.md](./04-business-tool-catalog.md) | DressnMore tool catalog |
| 05 | [event-flow.md](./05-event-flow.md) | Event catalog |
| 06 | [context-flow.md](./06-context-flow.md) | Context assembly path |
| 07 | [dependency-map.md](./07-dependency-map.md) | Allowed / forbidden deps |
| 08 | [extension-points.md](./08-extension-points.md) | Future extension seams |
| 09 | [gap-analysis.md](./09-gap-analysis.md) | Gaps & risks before prod binding |
| 10 | [readiness-report.md](./10-readiness-report.md) | Scored readiness assessment |
| 11 | [enterprise-integration-matrix.md](./11-enterprise-integration-matrix.md) | Module × contracts × events |
| 12 | [master-ai-execution-sequence.md](./12-master-ai-execution-sequence.md) | Canonical execution sequence |
| 13 | [business-tool-readiness-matrix.md](./13-business-tool-readiness-matrix.md) | Tool readiness by domain |
| 14 | [production-readiness-checklist.md](./14-production-readiness-checklist.md) | Production checklist |

## Governing principles (unchanged)

- DDD · SOLID · PSR · Hexagonal Architecture
- Contracts First · Event Driven
- Channel adapters never contain business logic
- Business Tools are the only Agent → Tenant Ops interface
- No redesign of frozen architecture in this review

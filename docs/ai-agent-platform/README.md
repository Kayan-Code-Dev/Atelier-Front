# DressnMore AI Agent Platform — Architecture Pack

**Status:** Architecture freeze (Phase 0)  
**Document type:** Enterprise Solution Architecture  
**Scope:** Design only — no application code, database schemas, API contracts, or UI specs in this pack.

## What this platform is

A **Digital Employee Operating System** for atelier tenants: an AI worker with permissions, operating modes, persona, tools, memory, and human collaboration.

It is **not** a chatbot product, WhatsApp bot, or generic AI assistant. WhatsApp (and every future messenger) is a **channel adapter only**.

## Pack contents

| Document | Purpose |
|----------|---------|
| [ENTERPRISE-ARCHITECTURE.md](./ENTERPRISE-ARCHITECTURE.md) | Master architecture: style, modules overview, principles, boundary |
| [module-catalog.md](./module-catalog.md) | Full module catalog (goal, I/O, dependencies) |
| [module-ownership.md](./module-ownership.md) | Ownership, criticality, Phase introduction |
| [message-journey.md](./message-journey.md) | End-to-end inbound message journey |
| [tenant-resolver.md](./tenant-resolver.md) | Channel→Tenant binding and isolation |
| [context-engine.md](./context-engine.md) | How context is assembled before model calls |
| [human-handover.md](./human-handover.md) | AI ↔ human ownership rules |
| [permission-engine.md](./permission-engine.md) | Capability firewall for the digital employee |
| [operating-modes.md](./operating-modes.md) | Assistant / Hybrid / Full Auto |
| [onboarding-wizard.md](./onboarding-wizard.md) | First-time WhatsApp activation wizard (conceptual) |
| [conversation-state-machine.md](./conversation-state-machine.md) | Conversation lifecycle states |
| [roadmap.md](./roadmap.md) | Phase 0 → Phase 5 roadmap |
| [phase-1-delivery-plan.md](./phase-1-delivery-plan.md) | Detailed Phase 1 vertical slice (still no code) |
| [adr/](./adr/) | Architecture Decision Records |
| [domain-model/](./domain-model/) | **Conceptual Domain Model (DDD)** — entities, contexts, language, events, invariants |
| [use-cases/](./use-cases/) | **System Behavior Specification** — use cases, intents, decision matrix, priority |
| [business-tools/](./business-tools/) | **Business Tool Contracts** — official Agent ↔ Domain tool contracts |
| [aos/SPRINT1-DEFINITION-OF-DONE.md](./aos/SPRINT1-DEFINITION-OF-DONE.md) | **Sprint 1 AOS Foundation** — DoD & validation |
| [aos/SPRINT2-DEFINITION-OF-DONE.md](./aos/SPRINT2-DEFINITION-OF-DONE.md) | **Sprint 2 Conversation Engine** — DoD & validation |
| [aos/SPRINT4-DEFINITION-OF-DONE.md](./aos/SPRINT4-DEFINITION-OF-DONE.md) | **Sprint 4 Business Tool Gateway** — DoD & validation |
| [aos/SPRINT5-DEFINITION-OF-DONE.md](./aos/SPRINT5-DEFINITION-OF-DONE.md) | **Sprint 5 Permission & Policy Engine** — DoD & validation |
| [aos/SPRINT6-DEFINITION-OF-DONE.md](./aos/SPRINT6-DEFINITION-OF-DONE.md) | **Sprint 6 AI Planner** — DoD & validation |
| [aos/SPRINT7-DEFINITION-OF-DONE.md](./aos/SPRINT7-DEFINITION-OF-DONE.md) | **Sprint 7 Prompt Engine** — DoD & validation |
| [aos/SPRINT8-DEFINITION-OF-DONE.md](./aos/SPRINT8-DEFINITION-OF-DONE.md) | **Sprint 8 Memory Engine** — DoD & validation |
| [aos/SPRINT9-DEFINITION-OF-DONE.md](./aos/SPRINT9-DEFINITION-OF-DONE.md) | **Sprint 9 Knowledge Engine** — DoD & validation |
| [aos/SPRINT10-DEFINITION-OF-DONE.md](./aos/SPRINT10-DEFINITION-OF-DONE.md) | **Sprint 10 AI Provider Platform** — DoD & validation |
| [aos/SPRINT11-DEFINITION-OF-DONE.md](./aos/SPRINT11-DEFINITION-OF-DONE.md) | **Sprint 11 Omni-Channel Communication Hub** — DoD & validation |
| [aos/SPRINT12-DEFINITION-OF-DONE.md](./aos/SPRINT12-DEFINITION-OF-DONE.md) | **Sprint 12 Workflow & Automation Engine** — DoD & validation |
| [aos/SPRINT13-DEFINITION-OF-DONE.md](./aos/SPRINT13-DEFINITION-OF-DONE.md) | **Sprint 13 AI Workspace** — DoD & validation |
| [aos/SPRINT14-DEFINITION-OF-DONE.md](./aos/SPRINT14-DEFINITION-OF-DONE.md) | **Sprint 14 Customer Domain Binding** — contracts-first DoD |
| [aos/SPRINT15-DEFINITION-OF-DONE.md](./aos/SPRINT15-DEFINITION-OF-DONE.md) | **Sprint 15 Reservation Domain Binding** — contracts-first DoD |
| [aos/SPRINT16-DEFINITION-OF-DONE.md](./aos/SPRINT16-DEFINITION-OF-DONE.md) | **Sprint 16 AI Tool Registry & Capability Platform** — DoD |
| [aos/SPRINT17-DEFINITION-OF-DONE.md](./aos/SPRINT17-DEFINITION-OF-DONE.md) | **Sprint 17 AI Tenant Integration Platform** — DoD |
| [aos/SPRINT18-DEFINITION-OF-DONE.md](./aos/SPRINT18-DEFINITION-OF-DONE.md) | **Sprint 18 AI Planner Engine** — DoD |
| [aos/SPRINT18A-DEFINITION-OF-DONE.md](./aos/SPRINT18A-DEFINITION-OF-DONE.md) | **Sprint 18A AI Platform Integration** — DoD |
| [aos/SPRINT20-DEFINITION-OF-DONE.md](./aos/SPRINT20-DEFINITION-OF-DONE.md) | **Sprint 20 AI Response Engine & E2E** — DoD |
| [aos/SPRINT21-DEFINITION-OF-DONE.md](./aos/SPRINT21-DEFINITION-OF-DONE.md) | **Sprint 21 Smart Assistant Architecture** — Frozen v1.0.0 |
| [integration/README.md](./integration/README.md) | **System Integration Review** — official AOS linkage & readiness pack |

## Fit with DressnMore today

- Custom multi-tenant: `X-Tenant` / query / subdomain → `tenant_{slug}` (app/API path).
- Channel traffic uses a **separate** Channel Binding Registry (clients do not send `X-Tenant`).
- Central DB: plans, subscriptions, platform RBAC, channel bindings registry.
- Tenant DB: customers, catalog/inventory, invoices/orders, deliveries, cashbox, settings — exposed to the agent only via **Business Tools** behind the Permission Engine.
- Existing in-app notifications are reused for staff alerts; WhatsApp Business API is not present yet and will land as Adapter #1.

## Explicit non-goals (this freeze)

- No code implementation
- No database migrations or table designs
- No REST/GraphQL API contracts
- No UI wireframes or screen specs
- No LLM vendor selection

## Related

- Platform tenancy overview: [`../architecture.md`](../architecture.md)
- Tenant isolation notes: [`../tenant-isolation.md`](../tenant-isolation.md)

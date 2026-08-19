# Module Ownership Map — AI Agent Platform

Ownership is logical (product + engineering accountability), not a staffing assignment. Criticality drives Phase introduction and review gates.

| Module | Owner domain | Criticality | Introduced in | Depends on |
|--------|--------------|-------------|-----------------|------------|
| Channel Adapter Layer | Channels / Integrations | High | Phase 1 (WhatsApp), Phase 3+ (others) | Ingress Port |
| Ingress Gateway | Platform Edge | Critical | Phase 1 | Adapters |
| Tenant Resolver & Isolation Guard | Security / Tenancy | Critical | Phase 1 | Binding Registry |
| Conversation Manager | Agent Core | Critical | Phase 1 | Resolver, State Machine |
| Conversation State Machine | Agent Core | Critical | Phase 1 | Conversation Manager |
| Contact & Identity Resolver | Agent Core + Customers | High | Phase 1 | Tenant Customers |
| AI Orchestrator | Agent Core | Critical | Phase 1 | Context, Perms, Tools, Handover |
| AI Context Engine | Agent Core | Critical | Phase 1 | Memory, KB, Domain reads |
| Conversation Memory | Agent Core | High | Phase 1 | Conversation Manager |
| Knowledge Base | Agent Knowledge | High | Phase 1 (basic), Phase 2 (rich) | — |
| Business Tools Gateway | Agent Core + Tenant Domain | Critical | Phase 1 (read), Phase 2 (write) | Permission Engine, Domain |
| Permission Engine | Policy / Security | Critical | Phase 1 (basic), Phase 2 (full) | Owner policy, Entitlements |
| Human Handover | Collaboration | Critical | Phase 1 | Conversation, Notifications |
| Reply Generator | Agent Core | High | Phase 1 | Orchestrator, Channel limits |
| Automation Engine | Automation | Medium | Phase 3 | Conversation, Tools, Notifications |
| Notification Service | Tenant Ops | High | Phase 1 | Existing TenantNotifier |
| Analytics & Quality | Insights | Medium | Phase 2 | Telemetry |
| AI Training & Persona Studio | Agent Knowledge | High | Phase 1 (wizard), Phase 2 (studio) | KB, Orchestrator |
| Audit / Compliance / Safety | Security | Critical | Phase 1 | Cross-cutting |
| Entitlement & Billing Gate | Platform Billing | High | Phase 1 | Central Plans |

## RACI (conceptual)

| Concern | Responsible | Accountable | Consulted | Informed |
|---------|-------------|-------------|-----------|----------|
| Channel-agnostic core integrity | Agent Core | Solution Architect | Channels | Product |
| Tenant isolation | Tenancy / Security | Security Lead | Agent Core | Support |
| Tool allow-list & ceilings | Product + Atelier Admin | Product | Finance/Ops domain owners | Tenant owners |
| Handover SLAs | Collaboration | Product | Tenant Ops | Analytics |
| Plan feature gating | Billing | Platform Admin | Agent Core | Sales |

## Boundary rules

1. Channel teams may only ship Adapters implementing `ChannelPort`.
2. Domain teams expose Tools via Gateway — never called directly from webhooks.
3. Policy changes require Audit events; silent permission expansion is forbidden.
4. Analytics is read-only; it must not mutate conversation ownership.

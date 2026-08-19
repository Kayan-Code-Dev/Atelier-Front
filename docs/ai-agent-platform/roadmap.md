# Roadmap — AI Agent Platform

## Phase 0 — Foundations (Architecture freeze) — CURRENT

- Accept Bounded Context, channel Ports, Permission model, State Machine
- Define Agent Profile and Channel Binding concepts
- Tenant isolation and Audit standards
- **Exit:** This documentation pack

## Phase 1 — Vertical Slice (WhatsApp + Hybrid + Read Tools)

- WhatsApp Channel Adapter
- Tenant Resolver via Binding
- Conversation Manager + basic State Machine
- Context Engine (profile + short memory + basic KB)
- Read-only Tools: customer, order/invoice status, item availability
- Hybrid Mode + basic Handover
- Initial Wizard for connect + activate
- **Exit:** Digital employee answers informationally and hands over when needed

Detail: [phase-1-delivery-plan.md](./phase-1-delivery-plan.md)

## Phase 2 — Write Tools + Permissions Depth

- Create/update tools within ceilings (booking, appointment, bounded invoice)
- Full Permission Engine + Approvals
- Persona Studio + richer KB
- Basic Analytics (containment, handover)
- **Exit:** Real business actions safely

## Phase 3 — Multi-Channel Expansion

- Same core + Adapters: Web Chat, then Messenger/Instagram
- Unified contact identity across channels
- Automation (delivery reminders / follow-ups)
- **Exit:** Multi-channel without rebuilding Orchestrator

## Phase 4 — Full Auto Hardening

- Production Full Auto Mode
- Quality evals, safety classifiers, token cost controls
- Telegram / Email / App Chat
- Advanced playbooks; policy copy across branches
- **Exit:** Sellable Enterprise digital-employee feature

## Phase 5 — Platform Scale

- SRE observability, multi-region considerations, precise plan metering
- Cross-tenant quality rubrics without sharing tenant data
- **Exit:** HubSpot / Zendesk-class operational maturity

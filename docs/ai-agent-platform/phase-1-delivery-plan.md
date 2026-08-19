# Phase 1 Delivery Plan — Vertical Slice

**Status:** Planned (architecture-approved; implementation not started)  
**Goal:** Ship the smallest end-to-end Digital Employee on WhatsApp in Hybrid Mode with read-only tools and human handover.  
**Still out of scope for Phase 1 coding kickoff docs:** DB schemas, API contracts, UI mockups (those are produced at implementation start, not in Architecture Freeze).

## 1. Objectives

1. Receive WhatsApp inbound messages for a bound atelier number.
2. Resolve tenant via Channel Binding only.
3. Maintain conversation state and ownership.
4. Answer informational questions using curated context + read tools.
5. Escalate complex/risky intents to human staff.
6. Activate via a conceptual wizard checklist (connect → persona → permissions → test → live).

## 2. In-scope modules (Phase 1)

| Module | Phase 1 depth |
|--------|----------------|
| WhatsApp Adapter | Inbound + outbound text (media deferred if needed) |
| Ingress Gateway | Signature, idempotency, queue |
| Tenant Resolver | Binding registry lookup + isolation key |
| Conversation Manager | Create/continue thread, message log |
| State Machine | New, ActiveAI, AwaitingCustomer, ActiveHuman, Resolved/Closed, PendingApproval (minimal) |
| Contact Resolver | Phone match to Customer; UnknownContact allowed |
| Context Engine | Policy, persona, short memory, customer, selective facts, basic KB |
| Memory | Short window + rolling summary |
| Knowledge Base | Seed from wizard atelier facts + FAQ |
| Business Tools | **Read-only:** customer lookup, invoice/order status, availability |
| Permission Engine | Capability allow-list for reads + reply; deny writes/deletes/refunds |
| Human Handover | Escalate + notify + return to AI |
| Reply Generator | Persona-aware text; WhatsApp length constraints |
| Notifications | In-app staff alert on escalate |
| Audit | Decision + tool + deny logs |
| Entitlements | Feature flag gate for AI Agent |
| Wizard | Conceptual activation flow |

## 3. Explicitly deferred

- Write tools (create invoice, refund, discount)
- Full Auto Mode as default
- Extra channels
- Automation engine
- Advanced analytics / token cost dashboards
- SharedAssist approval UI depth (basic escalate is enough)

## 4. Acceptance criteria (product)

1. Unbound WhatsApp traffic never touches a tenant DB.
2. Bound number routes only to its tenant.
3. Hybrid: FAQ/status/availability can be answered by AI when permitted.
4. Refund/delete/price-change intents escalate.
5. Staff can take ownership and return to AI.
6. Every tool call and deny is auditable.
7. Go-live blocked until Test Lab scenarios pass (availability, price question, complaint, refund request).

## 5. Workstreams (implementation-ready later)

1. **Edge & Channel** — Adapter + Ingress  
2. **Tenancy & Binding** — Registry + Resolver  
3. **Conversation Core** — Manager + FSM  
4. **Agent Brain** — Orchestrator + Context + Memory + KB seeds  
5. **Tools & Policy** — Read tools + Permission firewall  
6. **Collaboration** — Handover + Notifications  
7. **Activation** — Wizard checklist + Test Lab scenarios  
8. **Observability** — Audit + basic containment/handover counters  

## 6. Dependencies on existing DressnMore

- Tenant DB domain services for Customers / Invoices-Orders / Inventory reads
- Tenant in-app notification path
- Central plans/subscriptions for feature entitlement
- Existing human tenancy stack remains unchanged for staff apps

## 7. Exit to Phase 2

Phase 2 starts only when Phase 1 acceptance criteria are met in a pilot tenant and Architecture Freeze ADRs remain unviolated (especially channel-agnostic core and binding isolation).

# Customer Domain → AI Business Tools Mapping

**Sprint 14** mapping document.

## Purpose

Explain how DressnMore Customer Domain concepts become AOS-consumable Business Tools and context projections.

## Domain concept → Binding artifact

| Customer Domain concept | Binding artifact | AI consumer |
|-------------------------|------------------|-------------|
| Customer aggregate identity | `CustomerId` + `CustomerReadModel` | Resolver / Tools |
| Profile attributes | `CustomerContext.basicProfile` | Prompt / Planner |
| Measurements | `GetCustomerMeasurements` + context.measurements | Tailoring intents |
| Orders | `GetCustomerOrders` | Order status intents |
| Reservations | `GetCustomerReservations` | Booking intents |
| Invoices / payment posture | `GetCustomerInvoices` + paymentStatus | Billing intents |
| Notes | `GetCustomerNotes` | Support context |
| Channel history | `CustomerTimeline` | Conversation UX / Planner |
| VIP / tags / prefs | Snapshot + Context | Persona tone / priority |
| Duplicate twins | `MergeCustomers` (Critical/Always) | Ops with HITL |

## Tool mapping to existing AOS catalog (Integration Review)

| Sprint 14 Tool | Closest AOS Business Tool catalog name | Notes |
|----------------|----------------------------------------|-------|
| GetCustomer | GetCustomerProfile | Binding alias for AI clarity |
| SearchCustomer | SearchCustomer | Same |
| CreateCustomer | UpsertCustomerProfile (create path) | Create-focused contract |
| UpdateCustomer | UpsertCustomerProfile (update path) | Update-focused contract |
| GetCustomerHistory | (derived) | Composition of commercial + interaction history |
| GetCustomerMeasurements | GetMeasurements | Customer-scoped |
| GetCustomerReservations | ListReservationsForCustomer | Same intent |
| GetCustomerInvoices | ListInvoicesForCustomer | Same intent |
| GetCustomerOrders | ListOpenOrdersForCustomer | Includes broader statuses conceptually |
| GetCustomerNotes | SaveCustomerNote (read side) | Read notes contract |
| GetCustomerTimeline | — | New binding projection tool |
| CustomerExists | SearchCustomer (existence check) | Lightweight |
| MergeCustomers | — (new Critical) | Always approval |
| CustomerSummary | GenerateConversationSummary (customer-scoped) | Snapshot/summary |
| CustomerInsights | — | Placeholder insights for planner |

## Transformation rules

1. **Never** pass Eloquent models into AOS.  
2. Convert Domain entities → `CustomerReadModel` inside a future DressnMore adapter.  
3. Builders produce Context/Snapshot/Timeline only from Read Model.  
4. Tool Gateway sees contracts/capabilities — not SQL.  
5. Critical merges/phone-like mutations remain approval-gated.

## Event mapping

| Binding event | When | Downstream |
|---------------|------|------------|
| CustomerResolved | Resolver hit | Planner/Conversation attach customerRef |
| CustomerCreated/Updated/Merged | Future write adapters | Audit + Memory facts |
| CustomerContextBuilt | Context builder | Prompt/Memory injection |
| CustomerSnapshotBuilt / SummaryBuilt | Snapshot builder | Planner/Workflow variables |
| CustomerTimelineBuilt | Timeline builder | Workspace / analytics |

## Non-goals of this sprint

No Controllers, Routes, DB schemas, Laravel Models, Queries, Repository implementations against Tenant DB, HTTP, or live Tool execution against DressnMore services.

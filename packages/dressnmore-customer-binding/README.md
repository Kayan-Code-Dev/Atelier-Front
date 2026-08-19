# DressnMore Customer Domain Binding (`dressnmore/customer-binding`)

**Sprint 14** — First DressnMore ↔ AOS domain binding (Customer).

## Binding Philosophy

This package does **not** expose Eloquent, Controllers, or HTTP.  
It translates the Customer bounded context into **AOS Business Tool contracts** and AI-safe read shapes (Context / Snapshot / Timeline) so the Digital Employee can understand a customer without knowing DressnMore internals.

```
DressnMore Customer Domain
        ↓ (future adapters behind ports)
Customer Read Model Port
        ↓
Resolver / Context / Snapshot / Timeline Builders
        ↓
Customer Tool Contracts
        ↓
AOS Tool Gateway + Planner + Prompt + Memory + Workflow
```

## Architecture

Hexagonal + Contracts First:

| Layer | Contents |
|-------|----------|
| Contracts | Ports for resolver, builders, tool adapter, capabilities, policies, events |
| Domain | Tool catalog, read model DTO, context/snapshot/timeline, events |
| Application | Pure binding composers + catalog adapters |
| Infrastructure | In-memory ports for tests/demo only |

Excluded: Controllers, Routes, Database, Laravel Models, Queries, Domain service implementations, API, HTTP.

## Tool Adapters

`CustomerToolAdapter` publishes 15 conceptual tools:

GetCustomer · SearchCustomer · CreateCustomer · UpdateCustomer · GetCustomerHistory · GetCustomerMeasurements · GetCustomerReservations · GetCustomerInvoices · GetCustomerOrders · GetCustomerNotes · GetCustomerTimeline · CustomerExists · MergeCustomers · CustomerSummary · CustomerInsights

Each contract defines Purpose, Inputs, Outputs, Capability, Permission, Risk, Approval Policy, Expected Events.

## Customer Context

Full AI-facing context: profile, measurements, orders, reservations, invoices, payment status, language, preferences, VIP, tags, notes, last interaction, AI summary placeholder.

## Customer Snapshot

Compact projection for Planner, Memory, Knowledge, Workflow, Prompt Engine.

## Timeline

Omnichannel + commercial timeline sources: WhatsApp, Messenger, Instagram, Comments, Reservations, Invoices, Orders, Approvals, AI conversations, Human conversations.

## Extension Points

1. Replace `CustomerReadModelPortInterface` with a DressnMore adapter (still no Domain leakage upward).  
2. Register tool contracts into AOS Tool Gateway handlers.  
3. Bridge `CustomerEventPublisherInterface` to `aos-events` bus.  
4. Extend intent map / capabilities without changing AOS core.  

## Module

- Provider: `CustomerBindingServiceProvider`
- Module: `dressnmore.customer.binding`
- Mapping: `docs/CUSTOMER-DOMAIN-MAPPING.md`

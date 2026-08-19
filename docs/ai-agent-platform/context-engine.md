# AI Context Engine

## Purpose

Assemble a single, permission-aware context pack before any model invocation so the digital employee reasons over curated facts — not a raw data dump.

## Context layers (priority order)

1. **Runtime Policy** — Mode (Assistant / Hybrid / Full Auto) + Permission snapshot + Safety rules  
2. **Persona** — Digital employee name, tone, language, speaking limits  
3. **Conversation Memory** — Last N messages + long summary + open tasks  
4. **Customer Profile** — Name, phone, notes, history (if matched)  
5. **Operational Facts (structured reads)** — Related invoices/orders, delivery dates, rental/booking signals, interested products, balance/deposit if allowed  
6. **Knowledge Base** — Cancellation policies, working hours, general pricing, FAQ  
7. **Atelier Settings** — Branches, hours, published payment methods  
8. **Channel Constraints** — Max length, media support  

## Build strategy

| Strategy | Rule |
|----------|------|
| Selective hydration | Do not load all customer invoices; only what the detected intent needs |
| Permission-aware | Fields outside capability never enter context |
| Freshness | Structured facts read live from domain; KB from knowledge index |
| Compression | Summarize long chronologies |
| Single bundle | Orchestrator receives one pack stamped with `context_version` |

## Conceptual output contract

```
AgentContextBundle = {
  policy,
  persona,
  memory,
  customer,
  facts[],
  knowledge[],
  settings,
  channel_limits,
  citations[]
}
```

## DressnMore domain sources (read ports)

| Fact class | Typical tenant domain |
|------------|----------------------|
| Customer | Customers |
| Orders / invoices | Invoice / Rental / Tailoring / Sales |
| Deliveries | Deliveries / Returns |
| Catalog availability | Dress / Inventory / Branch |
| Payments / deposits | Invoice payments / Security deposit / Cashbox (if allowed) |
| Policies & FAQ | Knowledge Base + Settings |

## Non-goals

- Context Engine does not mutate business data
- Context Engine does not call channels
- Context Engine does not bypass Permission Engine redaction

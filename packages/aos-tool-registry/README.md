# AOS Tool Registry & Capability Platform (`dressnmore/aos-tool-registry`)

**Sprint 16** — Central discovery & registration platform for Business Tools.

## Purpose

Allow any Domain Binding (Customer, Reservation, Inventory, future ERP modules) to **register** tools, capabilities, intents, policies, and approvals into a central registry — while Planner and Tool Gateway depend only on **contracts**, never on domain internals.

This package does **not** execute tools. Execution remains in `aos-tools` Tool Gateway.

## Architecture

```
Domain Plugins (Customer / Reservation / …)
        ↓ ToolRegistrar
Tool Registry → Capability → Intent → Policy → Approval → Metadata → Provider
        ↓
Tool Discovery / Tool Resolver
        ↓
Planner · Prompt Engine · Workspace · Analytics
        ↓ (execution plane)
Tool Gateway (aos-tools)
```

## Binding Philosophy

- **Contracts First** — descriptors only, no handlers here
- **Plugin Architecture** — providers self-register via `ToolRegistrar`
- **Hexagonal** — Application registries behind ports
- Complements (does not replace) Sprint 4 Tool Gateway

## Key Components

| Component | Role |
|-----------|------|
| ToolRegistrar | Plugin entrypoint for domains |
| ToolRegistry / ToolCatalog | Registered tool descriptors |
| CapabilityRegistry | Domain capabilities (`Customer.Read`, …) |
| IntentRegistry | Intent → tool plan → capability → policy → approval |
| ToolDiscovery / ToolResolver | Planner-facing discovery & resolution |
| RegistrySnapshotBuilder | Snapshot for Prompt/Planner/Workspace |
| RegistryExporter | Conceptual catalog export |

## Documentation

See `docs/` — ARCHITECTURE, REGISTRATION-FLOW, DISCOVERY, CAPABILITIES, INTENT-MAPPING, VERSIONING, HEALTH, EXTENSIBILITY, validation-scenarios.

## Module

- Provider: `AosToolRegistryServiceProvider`
- Module: `aos.tool-registry`
- Version: `0.16.0`

# AOS Business Tool Gateway (`dressnmore/aos-tools`)

**Sprint 4** — Identifier-keyed gateway for discovering, authorizing, and executing Business Tools.

## Purpose

Provide the **only** path an Agent may use to invoke Business Tools.

This package:

- owns registry, discovery, resolution, validation, authorization hooks, execution pipeline
- does **not** implement DressnMore atelier business logic
- does **not** know OpenAI, Planner, Prompt Engine, WhatsApp, Messenger, Instagram, Eloquent, Controllers, or APIs

## Architecture

```
ToolGateway
  → ToolDiscovery / ToolResolver / ToolRegistry
  → ToolExecutionPipeline (extensible stages)
  → BusinessToolHandlerInterface (by ToolIdentifier)
  → ToolResult + Domain Events
```

Hexagonal: handlers and hooks are ports; Sprint 4 ships in-memory audit/analytics + capability authorization adapters.

## Lifecycle

```
Tool Requested
 → Discovered
 → Resolved
 → Metadata Loaded
 → Input Validated
 → Execution Context Created
 → Authorization Hook
 → Execute Tool
 → Normalize Result
 → Audit Hook
 → Analytics Hook
 → Return Result
```

## Execution Pipeline

Stages are independent `PipelineStageInterface` implementations assembled by `ToolPipelineFactory`.

| Stage | Role |
|-------|------|
| Discovery | Unknown tool → `NotFound` |
| Resolve | Load handler + manifest |
| Metadata | Attach `ToolMetadata` |
| Validation | Conceptual schema + mode policy |
| Execution Context | Lifecycle marker (context on request) |
| Authorization | Capability/permission hook |
| Execution | `ToolExecutor` → handler |
| Normalization | Ensure a `ToolResult` exists |
| Audit / Analytics | Opaque reference hooks |

Add stages via `ToolExecutionPipeline::withStage()` or by extending the factory.

## Registry

- Keyed by **`ToolIdentifier`** (e.g. `GetOrderStatus`), never by PHP class name
- Dynamic `register` / `unregister`
- Discovery by category, capability, and operating mode

Each manifest carries: identifier, version, category, description, capabilities, permissions, operating modes, risk level, supported intents, conceptual input/output schemas.

## Categories

Built-in: Customer, Reservation, Invoice, Payment, Order, Inventory, Knowledge, Notification, Automation, Analytics, Communication, Administration.

Custom categories: `ToolCategoryCode::custom('lead')`.

## Extension Points

1. Implement `BusinessToolHandlerInterface` and `ToolGateway::register()`
2. Swap `ToolAuthorizationHookInterface` for Permission Engine
3. Swap Audit / Analytics hooks for real sinks
4. Extend pipeline stages without changing the Gateway façade
5. Add categories via `ToolCategoryCode::custom()`

## Non-goals (this sprint)

- Real DressnMore Business Tool implementations
- Database / Eloquent
- HTTP Controllers / APIs
- Planner / AI / Channels

## Module

- Provider: `AosToolsServiceProvider`
- Module: `aos.tools`
- Feature flag: `business_tools`

## Tests / smoke

```bash
php scripts/aos-tools-smoke.php
php artisan test --filter=AosToolsGatewayTest
```

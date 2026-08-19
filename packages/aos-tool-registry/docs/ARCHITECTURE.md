# Architecture — AI Tool Registry & Capability Platform

## Layers

| Layer | Responsibility |
|-------|----------------|
| Contracts | Ports for registries, discovery, snapshot, export |
| Domain | Descriptors, enums, events, snapshot VO |
| Application | Registries, registrar, discovery, validation, bootstrap |
| Infrastructure | In-memory event publisher (tests/demo) |

## Separation from aos-tools

| Concern | Owner |
|---------|-------|
| Handler registration & execute pipeline | `aos-tools` Tool Gateway |
| Platform catalog, capabilities, intents, policies | `aos-tool-registry` (this package) |

Descriptors here can later be projected into ToolManifests without coupling domains to the gateway.

## Data flow

1. Domain Binding boots → calls `ToolRegistrar`
2. Capabilities registered before tools (validator enforces)
3. Tools registered with full metadata
4. Intents map business language → ordered tool plans
5. Planner uses `ToolDiscovery` / `ToolResolver` / `IntentResolver`
6. CapabilityValidator gates missing capabilities
7. Snapshot/Export feed Prompt Engine & Workspace

## Non-goals

Controllers · Routes · Database · Eloquent · HTTP · APIs · Tool execution implementations

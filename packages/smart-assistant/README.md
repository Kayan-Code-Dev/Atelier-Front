# Smart Assistant (`dressnmore/smart-assistant`)

**Architecture Foundation — Frozen v1.0.0** (Sprint 21)

Module: `smart.assistant` (alias: `smart-assistant`)

Contracts-first DDD foundation for DressnMore’s multi-channel digital employee.  
**No UI · No routes · No DB · No Planner/Gateway/LLM execution in this package.**

## Principles

Modular monolith ready · Multi-tenant first · DDD · Contracts first · Event driven · SOLID · Channel/Agent/Model agnostic · Open for extension

## Structure

```
Core · Conversations · Channels · Agents · Knowledge · Campaigns
Automations · Integrations · AI Models · Memory · Reports · Settings
```

## Docs

See [`docs/`](./docs/) — Architecture, Domain, Bounded Contexts, Layers, Agents, Channels, … DoD.

## Smoke

```bash
php scripts/smart-assistant-architecture-smoke.php
```

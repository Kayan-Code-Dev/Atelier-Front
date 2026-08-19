# AOS AI Tenant Integration Platform (`dressnmore/aos-tenant-ai`)

**Sprint 17** — Multi-tenant AI workspace layer for DressnMore.

## Goal

Give every Tenant an isolated AI Workspace with conversations, messages, session, context metadata, permissions, subscription gates, memory preferences, and integration bindings — without executing Planner/Gateway/LLM.

## Architecture

```
Tenant → AI Workspace → Conversation Manager → Context Builder
                              ↓
                    Permission ∩ Subscription
                              ↓
              Tool Registry → Gateway → Business Tools
```

## Contracts (providers)

Workspace · Conversation · Message · Context · Permission · Subscription · Memory · Integration · Session

In-memory adapters exist for tests/demo only.

## Dashboard IA

```
AI Assistant
├── Chat · History · Settings · Memory · Integrations · Usage
```

See `Domain/Dashboard/AiDashboardMenu`.

## Module

- Provider: `AosTenantAiServiceProvider`
- Module: `aos.tenant-ai`
- Version: `0.17.0`

## Docs

See `docs/` for Architecture, Workspace, Context, Conversations, Messages, Permissions, Subscriptions, Integrations, Events, Security, Validation, Extensibility.

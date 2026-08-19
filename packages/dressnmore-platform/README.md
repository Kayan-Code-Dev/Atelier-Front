# DressnMore Platform (`dressnmore/platform`)

**Sprint 18A — v0.18.5** — AI Assistant product integration into DressnMore.

Module: `platform.ai-integration`

## What this package does

Registers AI Assistant as a first-class tenant feature controlled by:

| Layer | Mechanism |
|-------|-----------|
| Module | `platform.ai-integration` in AOS Module Registry |
| Feature flag | `aos.feature_flags.ai_platform_integration` + `dressnmore-platform.ai.enabled_globally` |
| Package / plan | `ai_assistant.enabled` in Plan Feature Catalog |
| Tenant | optional denylist `DRESSNMORE_AI_TENANT_DISABLED` |
| RBAC | `ai.access`, `ai.chat`, `ai.history`, `ai.memory`, `ai.integrations`, `ai.settings`, `ai.usage` |

## Routes (tenant API)

Under authenticated `/api/tenant`:

- `GET /ai` — dashboard shell
- `GET /ai/navigation`
- `GET /ai/chat` · `/history` · `/settings` · `/memory` · `/integrations` · `/usage`

Middleware: `ai.feature` + `tenant.permission:…`

**Out of scope:** Planner, Gateway, LLM, WhatsApp, live conversations.

## Navigation

`/me` and `/login` include `navigation.ai_assistant` for the FE sidebar (visible only when module + plan + permissions allow).

## Docs

See [`docs/`](./docs/).

## Smoke

```bash
php scripts/aos-platform-ai-smoke.php
```

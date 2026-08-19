# Platform Integration

Sprint 18A wires AI Assistant into DressnMore SaaS:

1. **Module Registry** — `platform.ai-integration` via `AiIntegrationModule`
2. **Plan catalog** — `ai_assistant.enabled` / `.advanced` / chat limits (admin package UI)
3. **Permissions** — `ai.*` keys in `PermissionLabels` + seeders
4. **Tenant nav** — `navigation.ai_assistant` on login/`/me`
5. **HTTP surface** — `/api/tenant/ai/*` shell controllers
6. **Feature flags** — global + AOS flag + package + tenant denylist

Does **not** execute Planner, Gateway, tools, or LLM.

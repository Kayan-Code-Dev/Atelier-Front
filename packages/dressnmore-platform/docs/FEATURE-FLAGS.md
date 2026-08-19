# Feature Flags

| Flag | Location | Effect |
|------|----------|--------|
| Global | `dressnmore-platform.ai.enabled_globally` / `DRESSNMORE_AI_ENABLED` | Kill switch |
| AOS | `aos.feature_flags.ai_platform_integration` | Platform flag |
| Module | `aos.enabled_modules['platform.ai-integration']` | Module registry |
| Package | plan `ai_assistant.enabled` | Subscription |
| Tenant | `DRESSNMORE_AI_TENANT_DISABLED` (ids/slugs) | Per-tenant off |

All must allow before the feature is visible. RBAC is an additional layer.

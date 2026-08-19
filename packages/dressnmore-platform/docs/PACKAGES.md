# Packages

Plan feature keys (admin package management):

| Key | Basic | Pro | Enterprise |
|-----|-------|-----|------------|
| `ai_assistant.enabled` | off | on | on |
| `ai_assistant.advanced` | off | off | on |
| `ai_assistant.chat_monthly.max` | 100 | 500 | unlimited (0) |

`PlanFeatureSeeder` + `AiPackageSeeder` (upsert-only) keep these aligned.

Without `ai_assistant.enabled`: sidebar hidden, `/api/tenant/ai/*` rejected by `ai.feature` middleware.

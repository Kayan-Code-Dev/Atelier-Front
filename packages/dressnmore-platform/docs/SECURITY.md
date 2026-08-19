# Security

- Tenant isolation via existing tenant middleware stack
- Subscription enforcement via `PlanFeatureGate` + `EnsureAiFeatureEnabled`
- Permission enforcement via `tenant.permission:ai.*`
- No Planner/Gateway/LLM calls from this package (reduces attack surface)
- Direct URL access without package/permission → 403
- Admin plan changes immediately affect `enabled_modules` / gates on next `/me`

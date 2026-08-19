# Validation

Scenarios covered by smoke + unit catalogs:

| Scenario | Expected |
|----------|----------|
| Package without AI (basic) | Nav hidden, AI routes forbidden |
| User without permission | 403 on permission middleware |
| Module disabled | Feature not visible |
| Feature flag off | Feature not visible |
| Tenant denylisted | Feature not visible |
| Direct URL without package | `ai.feature` → 403 |
| Admin enables AI on plan | `ai_assistant` in `enabled_modules`, nav can show |
| Catalog paths/permissions | Unit tests |

Run: `php scripts/aos-platform-ai-smoke.php`

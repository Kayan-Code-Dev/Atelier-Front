# Validation

Covered in `tests/Unit/Platform/PlatformPlannerEngineTest.php`:

| Scenario | Expected |
|----------|----------|
| Unknown intent | Rejected |
| Conflicting intent (book+cancel) | Rejected |
| Missing capability | Rejected |
| Unregistered tool | Rejected |
| Tool blocked by policy | Rejected |
| Tool outside subscription | Rejected |
| User lacks permission | Rejected |
| Plan without tools | Rejected |
| Conflicting tools in plan | Rejected |
| Intent requires approval | `requires_approval` |
| Build failure | `failed` |
| Happy paths (book / customer / sales) | Ready or approval |

Sprint 6 scenarios remain in `tests/Unit/PlannerEngineTest.php`.

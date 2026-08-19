# Sprint 5 — Permission & Policy Engine · Definition of Done

## Goal

Ship the official **Permission & Policy Engine** that guards every AI action.

Package: `dressnmore/aos-permissions`  
Module: `aos.permissions`

## Done when

- [x] Authorization pipeline (context → mode → capabilities → permissions → policies → risk → decision/approval)
- [x] Operating modes (Assistant/Hybrid/Full Auto/Read Only/Human Only/Maintenance + custom)
- [x] Extensible capabilities + permissions registries with builtin catalog
- [x] Policy types + policy engine + resolver
- [x] Risk evaluator (Low→Critical)
- [x] Approval model (request/status/decision/chain/timeout/expiration)
- [x] Decision outcomes (Authorized/Denied/Approval Required/Human Escalation/Retry Later)
- [x] Domain events
- [x] Service provider + Kernel module registration
- [x] No DB/Eloquent/Controllers/APIs/OpenAI/Planner/channels/business tools
- [x] README + PHPUnit + smoke

## Validation

| Check | How |
|-------|-----|
| Install | `composer update dressnmore/aos-permissions --ignore-platform-reqs` |
| Smoke | `php scripts/aos-permissions-smoke.php` |
| Discover | `php artisan package:discover` |
| Tests | `php artisan test --filter=AosPermissionsEngineTest` |

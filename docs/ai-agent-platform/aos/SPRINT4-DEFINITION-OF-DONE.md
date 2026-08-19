# Sprint 4 — Business Tool Gateway · Definition of Done

## Goal

Ship an identifier-keyed **Business Tool Gateway** with registry, discovery, resolver, and extensible execution pipeline — **without** implementing DressnMore business tools.

Package: `dressnmore/aos-tools` (`packages/aos-tools`)  
Module: `aos.tools`

## Done when

- [x] Domain: manifests, requests, results, execution context, categories, risk, schemas
- [x] Registry keyed by ToolIdentifier (not class names)
- [x] Discovery + Resolver
- [x] Execution pipeline stages (validate → auth → execute → normalize → audit → analytics)
- [x] Authorization / Audit / Analytics hooks (contracts + in-memory adapters)
- [x] Domain events for request/resolve/execute/register/validation/auth
- [x] Service provider registers `aos.tools` with AOS Kernel
- [x] No DB / Eloquent / Controllers / APIs / OpenAI / Planner / Channels
- [x] README + PHPUnit + smoke

## Validation

| Check | How |
|-------|-----|
| Install | `composer update dressnmore/aos-tools --ignore-platform-reqs` |
| Discover | `php artisan package:discover` |
| Smoke | `php scripts/aos-tools-smoke.php` |
| Tests | `php artisan test --filter=AosToolsGatewayTest` |

## Non-goals confirmation

Real atelier tools, Tenant Ops adapters, Permission Engine product logic, Planner selection, and channel adapters remain out of scope.

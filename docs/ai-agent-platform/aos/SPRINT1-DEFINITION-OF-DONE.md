# Sprint 1 — AOS Foundation · Definition of Done

## Goal

Stable, extensible Agent Operating System **kernel** with three packages only:

- `dressnmore/aos-core`
- `dressnmore/aos-events`
- `dressnmore/aos-observability`

## Done when

- [x] Packages live under `packages/` with PSR-4 namespaces
- [x] `config/aos.php` exists (merged + published copy under `config/`)
- [x] Boot lifecycle reaches `ready`
- [x] Module registry contains `aos.core`, `aos.events`, `aos.observability`
- [x] Contracts documented via PHPDoc + package READMEs
- [x] Providers auto-discovered by Laravel
- [x] No business / AI / WhatsApp / planner / knowledge / tools code
- [x] No circular Composer dependencies (`events`/`observability` → `core` only)
- [x] Foundation scope ADR encoded in `FoundationScopeDecision`
- [x] Unit tests cover boot + module registration

## Validation checklist

| Check | How |
|-------|-----|
| Packages install | `composer update dressnmore/aos-core dressnmore/aos-events dressnmore/aos-observability` |
| Providers discovered | `php artisan package:discover` |
| Boot + modules | `php artisan test --filter=AosFoundationBootTest` (PHP ≥ 8.3) or `php scripts/aos-foundation-smoke.php` |
| Feature flags off | Assert `business_tools`, `ai_providers`, `channels_whatsapp` are false |
| No empty TODO stubs | Grep packages for `TODO` / `FIXME` (none required) |
| PSR autoload | Composer dump-autoload succeeds |
| Smoke (this environment) | `php scripts/aos-foundation-smoke.php` → PASSED |

## Non-goals confirmation

Stancl Tenancy was listed as a future stack preference; DressnMore Architecture Freeze uses **custom tenancy**. AOS Foundation remains **tenancy-agnostic** and does not introduce Stancl in Sprint 1.

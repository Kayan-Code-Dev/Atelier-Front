# Sprint 10 — AI Provider Platform · Definition of Done

## Goal

Ship an **AI Provider Platform** that selects and executes provider plugins through contracts — without SDKs, HTTP clients, or databases.

Package: `dressnmore/aos-ai`  
Module: `aos.ai`

## Done when

- [x] Provider + Model registries / catalogs
- [x] Selection pipeline (capabilities → filters → policies → health → select → execute → normalize)
- [x] Stub plugins for conceptual providers
- [x] Fallback, retry, health, metrics, budget/latency policies
- [x] Streaming pipeline (conceptual)
- [x] Domain events
- [x] Service provider + Kernel module
- [x] No OpenAI SDK, HTTP, DB, Controllers
- [x] README + PHPUnit + smoke

## Validation

| Check | How |
|-------|-----|
| Install | `composer update dressnmore/aos-ai --ignore-platform-reqs` |
| Smoke | `php scripts/aos-ai-smoke.php` |
| Discover | `php artisan package:discover` |
| Tests | `php artisan test --filter=AosAi` (requires PHP 8.3+) |

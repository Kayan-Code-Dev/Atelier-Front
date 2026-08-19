# Sprint 7 — Prompt Engine · Definition of Done

## Goal

Ship a **Prompt Engine** that builds versioned, guarded, provider-agnostic prompts from opaque planning/context inputs — without AI providers or persistence.

Package: `dressnmore/aos-prompts`  
Module: `aos.prompts`

## Done when

- [x] Prompt pipeline (persona → mode → tenant → rules → context → safety → optimize → validate → ready)
- [x] Persona engine (8 built-in personas)
- [x] Prompt sections + templates + versioning
- [x] Prompt Guard / Validator / Optimizer / Sanitizer
- [x] Domain events
- [x] Service provider + Kernel module
- [x] No OpenAI, Claude, Gemini, WhatsApp, Tool implementations, DB, APIs
- [x] README + PHPUnit + smoke

## Validation

| Check | How |
|-------|-----|
| Install | `composer update dressnmore/aos-prompts --ignore-platform-reqs` |
| Smoke | `php scripts/aos-prompts-smoke.php` |
| Discover | `php artisan package:discover` |
| Tests | `php artisan test --filter=AosPrompts` (requires PHP 8.3+) |
| Smoke (PHP 8.2+) | `php scripts/aos-prompts-smoke.php` |

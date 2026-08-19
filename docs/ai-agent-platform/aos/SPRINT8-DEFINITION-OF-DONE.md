# Sprint 8 — Memory Engine · Definition of Done

## Goal

Ship a **Memory Engine** that stores classified facts, retrieves ranked memory context, consolidates duplicates, and enforces isolation — without AI providers or databases.

Package: `dressnmore/aos-memory`  
Module: `aos.memory`

## Done when

- [x] Write pipeline (extract → classify → policy → score → dedupe → summarize → consolidate → store → index)
- [x] Retrieval pipeline (working → conversation → customer → business → rank → compress placeholder → context)
- [x] Memory types + policies + ranking + summaries + snapshots
- [x] Domain events
- [x] Service provider + Kernel module
- [x] No OpenAI/Claude/Gemini, Business Tools, WhatsApp, DB, APIs
- [x] No raw message persistence as durable memory
- [x] README + PHPUnit + smoke

## Validation

| Check | How |
|-------|-----|
| Install | `composer update dressnmore/aos-memory --ignore-platform-reqs` |
| Smoke | `php scripts/aos-memory-smoke.php` |
| Discover | `php artisan package:discover` |
| Tests | `php artisan test --filter=AosMemory` (requires PHP 8.3+) |

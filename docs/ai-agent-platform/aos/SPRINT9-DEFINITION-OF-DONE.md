# Sprint 9 — Knowledge Engine · Definition of Done

## Goal

Ship a **Knowledge Engine** that manages classified, versioned, published knowledge and retrieves ranked context — without embeddings, vector databases, or LLMs.

Package: `dressnmore/aos-knowledge`  
Module: `aos.knowledge`

## Done when

- [x] Knowledge types, sources, collections, lifecycle, versioning
- [x] Retrieval pipeline (search → rank → policy → isolation → context)
- [x] Lexical search port (swappable)
- [x] Domain events
- [x] Service provider + Kernel module
- [x] No OpenAI/Claude/Gemini, embeddings, vector DB, Business Tools, Planner, WhatsApp, DB, APIs
- [x] Global + Tenant knowledge with isolation
- [x] README + PHPUnit + smoke

## Validation

| Check | How |
|-------|-----|
| Install | `composer update dressnmore/aos-knowledge --ignore-platform-reqs` |
| Smoke | `php scripts/aos-knowledge-smoke.php` |
| Discover | `php artisan package:discover` |
| Tests | `php artisan test --filter=AosKnowledge` (requires PHP 8.3+) |

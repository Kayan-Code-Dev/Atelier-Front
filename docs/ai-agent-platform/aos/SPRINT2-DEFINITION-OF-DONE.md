# Sprint 2 — Conversation Engine · Definition of Done

## Goal

Ship a **Conversation Engine** package that owns conversation lifecycle only:

- Package: `dressnmore/aos-conversation` (`packages/aos-conversation`)
- Module: `aos.conversation`
- No OpenAI / WhatsApp / Tools / Knowledge / Planner / Eloquent / APIs

## Done when

- [x] Domain layer: entities, VOs, aggregate, factory, specs, events, policies, repository contract
- [x] State machine with illegal transition rejection
- [x] Ownership model + policies (AI / Human / SharedAssist / System)
- [x] Timeline + Session + Message support
- [x] Application: `ConversationManager` + `ConversationLifecycle`
- [x] In-memory repository adapter (no DB)
- [x] Service provider registers module with AOS Kernel
- [x] README documents purpose / lifecycle / aggregate / extension points
- [x] PHPUnit coverage for creation, transitions, ownership, timeline, session, illegal transition
- [x] PHPDoc + PSR-4 + Hexagonal ports

## Validation

| Check | How |
|-------|-----|
| Install | `composer update dressnmore/aos-conversation` |
| Discover | `php artisan package:discover` |
| Unit (app) | `php artisan test --filter=AosConversationEngineTest` |
| Unit (package) | `php vendor/bin/phpunit -c packages/aos-conversation/phpunit.xml` |
| Smoke | `php scripts/aos-conversation-smoke.php` |

## Non-goals confirmation

Planner, AI providers, Context/Memory, Knowledge, Business Tools, Channels, OpenAI, Laravel Models, and Database schemas are **out of scope** for Sprint 2.

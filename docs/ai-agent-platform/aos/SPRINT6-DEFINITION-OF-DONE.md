# Sprint 6 — AI Planner · Definition of Done

## Goal

Ship a **Planner Engine** that produces immutable Execution Plans from inbound messages — without LLM calls or tool execution.

Package: `dressnmore/aos-planner`  
Module: `aos.planner`

## Done when

- [x] Planning pipeline (context → intent → goals → tasks → dependencies → plan → validation → decision)
- [x] Intent kinds: Single / Multi / Ambiguous / Conflicting / Unknown
- [x] Immutable ExecutionPlan + ExecutionGraph
- [x] Clarification + Escalation evaluators
- [x] Domain events
- [x] Service provider + Kernel module
- [x] No OpenAI, Tool execution, WhatsApp, DB, APIs
- [x] README + PHPUnit + smoke

## Validation

| Check | How |
|-------|-----|
| Install | `composer update dressnmore/aos-planner --ignore-platform-reqs` |
| Smoke | `php scripts/aos-planner-smoke.php` |
| Discover | `php artisan package:discover` |
| Tests | `php artisan test --filter=AosPlannerEngineTest` |

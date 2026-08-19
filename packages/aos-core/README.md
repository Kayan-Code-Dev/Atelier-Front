# dressnmore/aos-core

## Purpose

Foundation **Agent Operating System (AOS)** kernel for DressnMore. Sprint 1 ships boot lifecycle, module registry, configuration, versioning, and health-check contracts — nothing else.

## Responsibilities

- Application kernel boot lifecycle
- Module loader + registry
- Configuration provider (`config/aos.php`)
- Version provider
- Core health check
- Foundation scope ADR (`FoundationScopeDecision`)

## Dependencies

- PHP `^8.3`
- `illuminate/support`, `illuminate/contracts`

Does **not** depend on `aos-events` or `aos-observability` at Composer level (avoids cycles). Those modules register themselves into the shared `ModuleRegistryInterface`.

## Extension points

- Implement `ModuleInterface` / extend `AbstractModule`
- Tag `HealthCheckInterface` implementations as `aos.health_checks`
- Publish config: `php artisan vendor:publish --tag=aos-config`

## Explicitly out of scope (Sprint 1)

Business tools, conversations, planner, knowledge, WhatsApp, OpenAI/AI providers.

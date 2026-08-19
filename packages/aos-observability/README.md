# dressnmore/aos-observability

## Purpose

Observability **ports** for AOS: logging, audit recording, metrics, tracing, health reporting, and performance sampling.

## Responsibilities

- Logger / Audit / Metrics / Tracer contracts
- Health reporter aggregating tagged `aos.health_checks`
- Performance collector (in-memory foundation adapter)
- Null/safe adapters so the kernel can boot without external APM

## Dependencies

- `dressnmore/aos-core`
- `psr/log`
- `illuminate/support`

## Extension points

- Replace `NullMetrics` / `NullTracer` with real backends in later sprints
- Replace `LoggingAuditRecorder` with durable audit store later
- Add more `HealthCheckInterface` services tagged `aos.health_checks`

## Out of scope

No product metrics dashboards, no OpenTelemetry exporters, no business audit schemas in Sprint 1.
